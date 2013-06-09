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
    protected $name;
    protected $build;

    public function __construct($name = null, $build = null)
    {
        $this->setName($name);
        $this->setBuild($build);
    }

    public function getBuild()
    {
        return $this->build;
    }

    public function setBuild($build)
    {
        $this->build = (string) $build;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = (string) $name;

        return $this;
    }

    public function getUID()
    {
        return sprintf('%s:%s', $this->name, $this->build);
    }

    public function __toString()
    {
        return $this->getUID();
    }

    public function equals(Version $version)
    {
        return (
                $this->name === $version->getName()
            &&  $this->build === $version->getBuild()
        );
    }
}
