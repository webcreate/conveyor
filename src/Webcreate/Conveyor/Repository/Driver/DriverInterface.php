<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Repository\Driver;

use Webcreate\Conveyor\Repository\Version;

interface DriverInterface
{
    public function getVersions();
    public function export($svnPath, $dest);
    public function diff(Version $oldVersion, Version $newVersion);
    public function changelog(Version $oldVersion, Version $newVersion);
    public function revisionCompare($revision1, $revision2);
}
