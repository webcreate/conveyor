<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Webcreate\Conveyor\Repository\Version;
use Webcreate\Conveyor\Repository\Driver\SvnDriver;

class SvnDriverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group integration
     */
    public function testDiff()
    {
        $driver = new SvnDriver('svn://vcs.wbcrte.net/fieg_fieg');

        $oldVersion = new Version('dev-trunk', 2);
        $newVersion = new Version('dev-trunk', 9);

        $result = $driver->diff($oldVersion, $newVersion);

        $this->assertInternalType('array', $result);
    }
}
