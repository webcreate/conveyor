<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Repository\Driver;

use Webcreate\Vcs\Common\Reference;
use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\Repository\Version;
use Webcreate\Vcs\VcsInterface;

abstract class AbstractVcsDriver implements DriverInterface
{
    protected $url;
    protected $client;
    protected $io;

    public function __construct($url, IOInterface $io = null)
    {
        $this->url = $url;
        $this->io = $io;
        $this->client = $this->getClient($url);
    }

    /**
     * @param $url
     * @return VcsInterface
     */
    abstract protected function getClient($url);

    public function getVersions()
    {
        $versions = array();

        $tags = $this->client->tags();
        foreach ($tags as $tag) {
            $versions[] = $this->createVersion($tag->getName(), $tag->getRevision());
        }

        $branches = $this->client->branches();
        foreach ($branches as $branch) {
            $versions[] = $this->createVersion($branch->getName(), $branch->getRevision(), 'dev');
        }

        return $versions;
    }

    public function getHead(Version $version)
    {
        if ($version->getName() == 'dev-trunk') {
            $head = new Reference('trunk');
        } elseif (substr($version->getName(), 0, 4) == 'dev-') {
            $head = new Reference(substr($version->getName(), 4));
        } else {
            $head = new Reference($version->getName(), Reference::TAG);
        }

        return $head;
    }

    protected function createVersion($name, $revision, $prefix = null)
    {
        $name = ($prefix ? $prefix . '-' : '') . $name;

        $version = new Version();
        $version->setName($name);
        $version->setBuild($revision);

        return $version;
    }

    public function export($version, $dest)
    {
        $head = $this->getHead($version);
        $this->client->setHead($head);

        /*
         * @todo Collect output of export process
         *
         * if ($this->io) {
         *   $io = $this->io;
         *   $this->client->setOutput(function($type, $output) use ($io) {
         *       if ($io->isVerbose()) {
         *           $io->write($output, false);
         *       }
         *   });
         *}
         */

        $result = $this->client->export('', $dest);

        //$this->client->setOutput(null);
        return $result;
    }

    public function diff(Version $oldVersion, Version $newVersion)
    {
        $head = $this->getHead($oldVersion);
        $this->client->setHead($head);

        $result = $this->client->diff(
            $this->getHead($oldVersion), $this->getHead($newVersion),
            $oldVersion->getBuild(), $newVersion->getBuild()
        );

        return $result;
    }

    public function revisionCompare($revision1, $revision2)
    {
        return $this->client->revisionCompare($revision1, $revision2);
    }

    public function changelog(Version $version1, Version $version2)
    {
        return $this->client->changelog($version1->getBuild(), $version2->getBuild());
    }
}
