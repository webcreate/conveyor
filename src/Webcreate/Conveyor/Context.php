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

use Webcreate\Conveyor\Repository\Version;
use Webcreate\Conveyor\Strategy\StrategyInterface;
use Webcreate\Conveyor\Util\FileCollection;

class Context
{
    protected $isFullDeploy = false;
    protected $isSimulate = false;
    protected $builddir;
    protected $filesModified;
    protected $filesDeleted;
    protected $version;
    protected $remoteVersion;
    protected $target;
    protected $strategy;

    public function __construct()
    {
        $this->filesModified = new FileCollection();
        $this->filesDeleted = new FileCollection();
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
     * @param  \Webcreate\Conveyor\Util\FileCollection $filesDeleted
     * @return $this
     */
    public function setFilesDeleted($filesDeleted)
    {
        $this->filesDeleted = $filesDeleted;

        return $this;
    }

    /**
     * @return \Webcreate\Conveyor\Util\FileCollection
     */
    public function getFilesDeleted()
    {
        return $this->filesDeleted;
    }

    /**
     * @param  \Webcreate\Conveyor\Util\FileCollection $filesModified
     * @return $this
     */
    public function setFilesModified($filesModified)
    {
        $this->filesModified = $filesModified;

        return $this;
    }

    /**
     * @return \Webcreate\Conveyor\Util\FileCollection
     */
    public function getFilesModified()
    {
        return $this->filesModified;
    }

    public function getBuilddir()
    {
        return $this->builddir;
    }

    /**
     * @param string $builddir
     */
    public function setBuilddir($builddir)
    {
        $this->builddir = $builddir;

        $this->filesModified->setBasepath($this->builddir);
        $this->filesDeleted->setBasepath($this->builddir);

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

    /**
     * @param boolean $value
     */
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
