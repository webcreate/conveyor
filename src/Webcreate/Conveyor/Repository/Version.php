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

class Version
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $build;

    /**
     * @param string|null $name
     * @param string|null $build
     */
    public function __construct($name = null , $build = null)
    {
        if (null !== $name) {
            $this->setName($name);
        }

        if (null !== $build) {
            $this->setBuild($build);
        }
    }

    /**
     * @return null|string
     */
    public function getBuild()
    {
        return $this->build;
    }

    /**
     * @param string $build
     * @return $this
     */
    public function setBuild($build)
    {
        $this->build = (string) $build;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = (string) $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getUID()
    {
        return sprintf('%s:%s', $this->name, $this->build);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getUID();
    }

    /**
     * @param Version $version
     * @return bool
     */
    public function equals(Version $version)
    {
        return (
                $this->name === $version->getName()
            &&  $this->build === $version->getBuild()
        );
    }
}
