<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Strategy;

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
     * @return string
     */
    public function getUploadPath(Version $version);

    /**
     * Sets options
     *
     * @param  array $options
     * @return mixed
     */
    public function setOptions(array $options);
}
