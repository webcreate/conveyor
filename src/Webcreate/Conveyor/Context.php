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
    protected $buildDir;
    protected $filesModified;
    protected $filesDeleted;

    /**
     * @var Version
     */
    protected $version;

    /**
     * @var Version
     */
    protected $remoteVersion;

    /**
     * @var string
     */
    protected $target;

    /**
     * @var StrategyInterface
     */
    protected $strategy;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->filesModified = new FileCollection();
        $this->filesDeleted = new FileCollection();
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @return mixed
     */
    public function getRemoteVersion()
    {
        return $this->remoteVersion;
    }

    /**
     * @param Version $remoteVersion
     * @return $this
     */
    public function setRemoteVersion(Version $remoteVersion)
    {
        $this->remoteVersion = $remoteVersion;

        return $this;
    }

    /**
     * @param string $target
     * @return $this
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @return Version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param Version $version
     * @return $this
     */
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

    /**
     * @return mixed
     */
    public function getBuilddir()
    {
        return $this->buildDir;
    }

    /**
     * @param string $builddir
     * @return $this
     */
    public function setBuilddir($builddir)
    {
        $this->buildDir = $builddir;

        $this->filesModified->setBasepath($this->buildDir);
        $this->filesDeleted->setBasepath($this->buildDir);

        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFullDeploy($value)
    {
        $this->isFullDeploy = (bool) $value;

        return $this;
    }

    /**
     * @return bool
     */
    public function isFullDeploy()
    {
        return $this->isFullDeploy;
    }

    /**
     * @param boolean $value
     * @return $this
     */
    public function setSimulate($value)
    {
        $this->isSimulate = (bool) $value;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSimulate()
    {
        return $this->isSimulate;
    }

    /**
     * @param StrategyInterface $strategy
     * @return $this
     */
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
