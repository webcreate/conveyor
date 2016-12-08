<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Repository;

use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\Repository\Driver\DriverInterface;

class Repository
{
    protected $drivers = array();

    /** @var  DriverInterface */
    protected $driver;
    protected $type;
    protected $url;
    protected $io;
    protected $cacheDir;

    /**
     * @param string           $type     name of a registered driver
     * @param string           $url
     * @param null|IOInterface $io
     * @param null|array       $drivers
     * @param null|string      $cacheDir
     */
    public function __construct($type, $url, IOInterface $io = null, array $drivers = null, $cacheDir = null)
    {
        $this->drivers = $drivers ? $drivers : array(
            'svn' => 'Webcreate\\Conveyor\\Repository\\Driver\\SvnDriver',
            'git' => 'Webcreate\\Conveyor\\Repository\\Driver\\GitDriver',
        );

        $this->io   = $io;
        $this->type = $type;
        $this->url  = $url;
        $this->cacheDir = preg_replace('/^\~/', getenv('HOME'), $cacheDir);
    }

    /**
     * @return DriverInterface
     * @throws \RuntimeException
     */
    protected function getDriver()
    {
        if (isset($this->driver)) {
            return $this->driver;
        }

        if (isset($this->drivers[$this->type])) {
            $driver = $this->drivers[$this->type];

            if (is_string($driver)) {
                $this->driver = new $driver($this->url, $this->io);
            } else {
                $this->driver = $driver;
            }

            $this->driver->setCacheDir($this->cacheDir);
            $this->driver->initialize($this->url);
        }

        if (!$this->driver) {
            throw new \RuntimeException(sprintf('Driver for type \'%s\' not found', $this->type));
        }

        return $this->driver;
    }

    public function addDriver($type, DriverInterface $driver)
    {
        $this->drivers[$type] = $driver;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @todo move this to webcreate/vcs library
     *
     * @return string
     */
    public function getMasterBranch()
    {
        $branch = 'unknown';

        switch ($this->getType()) {
            case "git":
                $branch = 'master';
                break;
            case "svn":
                $branch = 'trunk';
                break;
        }

        return $branch;
    }

    /**
     * @return Version[]
     */
    public function getVersions()
    {
        return $this->getDriver()->getVersions();
    }

    /**
     * @param string $name
     * @throws \InvalidArgumentException
     * @return Version
     */
    public function getVersion($name)
    {
        foreach ($this->getVersions() as $version) {
            if ($version->getName() === $name) {
                return $version;
            }
        }

        throw new \InvalidArgumentException(sprintf('Version \'%s\' not found', $name));
    }

    public function export(Version $version, $dest)
    {
        return $this->getDriver()->export($version, $dest);
    }

    /**
     * @param  Version                             $oldVersion
     * @param  Version                             $newVersion
     * @return \Webcreate\Vcs\Common\VcsFileInfo[]
     */
    public function diff(Version $oldVersion, Version $newVersion)
    {
        return $this->getDriver()->diff($oldVersion, $newVersion);
    }

    /**
     * @param Version $version1
     * @param Version $version2
     * @return int
     */
    public function versionCompare(Version $version1, Version $version2)
    {
        $build1 = $version1->getBuild();
        $build2 = $version2->getBuild();

        return $this->getDriver()->revisionCompare($build1, $build2);
    }

    public function changelog(Version $version1, Version $version2)
    {
        return $this->getDriver()->changelog($version1, $version2);
    }
}
