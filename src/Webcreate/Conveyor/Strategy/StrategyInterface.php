<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Strategy;

use Webcreate\Conveyor\Context;
use Webcreate\Conveyor\Repository\Version;

interface StrategyInterface
{
    /**
     * Returns an array contain the required directories relative
     * to the target's basepath
     *
     * Used to validate the remote directory structure in the ValidateRemoteStage
     *
     * @return string[]
     */
    public function getRequiredDirectories();

    /**
     * Returns the relative path to the current release
     *
     * @return string
     */
    public function getCurrentReleasePath();

    /**
     * Returns the upload path for a specific version
     *
     * @param $version
     * @return mixed
     */
    public function getUploadPath(Version $version);
}


