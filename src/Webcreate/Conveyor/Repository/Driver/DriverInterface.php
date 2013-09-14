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

use Webcreate\Conveyor\Repository\Version;

interface DriverInterface
{
    public function setCacheDir($cacheDir);
    public function getVersions();
    public function export($svnPath, $dest);
    public function diff(Version $oldVersion, Version $newVersion);
    public function changelog(Version $oldVersion, Version $newVersion);
    public function revisionCompare($revision1, $revision2);
    public function initialize();
}
