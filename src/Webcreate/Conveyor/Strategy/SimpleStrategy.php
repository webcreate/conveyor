<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Strategy;

use Webcreate\Conveyor\Context;
use Webcreate\Conveyor\Repository\Version;

class SimpleStrategy implements StrategyInterface
{
    /**
     * Returns an array contain the required directories relative
     * to the target's basepath
     *
     * @return string[]
     */
    public function getRequiredDirectories()
    {
        return array();
    }

    /**
     * Returns the relative path to the current release
     *
     * @return string
     */
    public function getCurrentReleasePath()
    {
        return '';
    }

    /**
     * Returns the upload path for a specific version
     *
     * @param  \Webcreate\Conveyor\Repository\Version $version
     * @return mixed
     */
    public function getUploadPath(Version $version)
    {
        return '';
    }
}
