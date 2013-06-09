<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Webcreate\Conveyor\Task\ExportTask;

class ExportTaskTest extends PHPUnit_Framework_TestCase
{
    protected $tempdir;

    public function setUp()
    {
        $this->tempdir = sys_get_temp_dir();
    }

    public function testExecuteDelegatesExportToRepository()
    {
        $version = $this->getMock('Webcreate\\Conveyor\\Repository\\Version');
        $repository = $this->getMock('Webcreate\\Conveyor\\Repository\\Repository', array(), array(), '', false);
        $repository
            ->expects($this->once())
            ->method('export')
            ->with($version, $this->tempdir)
        ;
        $task = new ExportTask($this->tempdir, $repository);

        $task->execute('blaat', $version);
    }
}
