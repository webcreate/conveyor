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
use Webcreate\Vcs\Common\VcsFileInfo;

interface DriverInterface
{
    /**
     * @param  string $cacheDir
     * @return void
     */
    public function setCacheDir($cacheDir);

    /**
     * @return Version[]
     */
    public function getVersions();

    /**
     * @param Version $svnPath
     * @param string  $dest
     * @return
     */
    public function export(Version $svnPath, $dest);

    /**
     * @param  Version       $oldVersion
     * @param  Version       $newVersion
     * @return VcsFileInfo[]
     */
    public function diff(Version $oldVersion, Version $newVersion);

    /**
     * @param  Version $oldVersion
     * @param  Version $newVersion
     * @return mixed
     */
    public function changelog(Version $oldVersion, Version $newVersion);

    /**
     * @param  string  $revision1
     * @param  string  $revision2
     * @return integer
     */
    public function revisionCompare($revision1, $revision2);

    /**
     * @param  string $url repository url
     * @return void
     */
    public function initialize($url);
}
