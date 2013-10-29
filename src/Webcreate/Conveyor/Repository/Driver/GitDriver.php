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

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\Repository\Version;
use Webcreate\Vcs\Git;

class GitDriver extends AbstractVcsDriver
{
    protected $dispatcher;

    public function __construct($url, IOInterface $io = null, EventDispatcherInterface $dispatcher = null)
    {
        $this->url        = $url;
        $this->io         = $io;
        $this->dispatcher = $dispatcher;
    }

    protected function getClient($url)
    {
        $client = new Git($url);
        $client->getAdapter()->setExecutable('git');

        if (null !== $this->dispatcher) {
            $client->setDispatcher($this->dispatcher);
        }

        $client->setCwd($this->cacheDir . '/git/' . md5($url));

        return $client;
    }

    /**
     * FIXME passing `null`, `null` failed on svn client, but looks like
     *       it's needed by the git client. Fix the git client to accept
     *       Reference's correctly!
     *
     * @param  Version $oldVersion
     * @param  Version $newVersion
     * @return mixed
     */
    public function diff(Version $oldVersion, Version $newVersion)
    {
        $head = $this->getHead($oldVersion);
        $this->client->setHead($head);

        $result = $this->client->diff(
            null, null,
            $oldVersion->getBuild(), $newVersion->getBuild()
        );

        return $result;
    }
}
