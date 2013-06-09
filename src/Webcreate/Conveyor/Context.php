<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor;

use Webcreate\Conveyor\Strategy\StrategyInterface;
use Webcreate\Conveyor\Util\FileCollection;
use Webcreate\Conveyor\Repository\Version;

class Context
{
    protected $isFullDeploy = false;
    protected $isSimulate = false;
    protected $builddir;
    protected $filelist;
    protected $version;
    protected $remoteVersion;
    protected $target;
    protected $strategy;

    public function __construct()
    {
        $this->filelist = new FileCollection();
    }

    public function getTarget()
    {
        return $this->target;
    }

    public function getRemoteVersion()
    {
        return $this->remoteVersion;
    }

    public function setRemoteVersion(Version $remoteVersion)
    {
        $this->remoteVersion = $remoteVersion;

        return $this;
    }

    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion(Version $version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return \Webcreate\Conveyor\Util\FileCollection
     */
    public function getFilelist()
    {
        return $this->filelist;
    }

    public function setFilelist(FileCollection $filelist)
    {
        $this->filelist = $filelist;

        return $this;
    }

    public function getBuilddir()
    {
        return $this->builddir;
    }

    public function setBuilddir($builddir)
    {
        $this->builddir = $builddir;

        $this->filelist->setBasepath($this->builddir);

        return $this;
    }

    public function setFullDeploy($value)
    {
        $this->isFullDeploy = (bool) $value;

        return $this;
    }

    public function isFullDeploy()
    {
        return $this->isFullDeploy;
    }

    public function setSimulate($value)
    {
        $this->isSimulate = (bool) $value;

        return $this;
    }

    public function isSimulate()
    {
        return $this->isSimulate;
    }

    public function setStrategy(StrategyInterface $strategy)
    {
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * @return StrategyInterface
     */
    public function getStrategy()
    {
        return $this->strategy;
    }
}
