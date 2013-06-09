<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Webcreate\Conveyor\Task\ShellTask;

class ShellTaskTest extends \PHPUnit_Framework_TestCase
{
    /** @var PHPUnit_Framework_MockObject_MockObject */
    protected $cli;
    protected $tempdir;

    public function setUp()
    {
        $this->tempdir = sys_get_temp_dir();
        $this->cli = $this->getMock('Webcreate\\Util\\Cli');
    }

    public function testExecuteReturnsExecuteResult()
    {
        $this->cli
            ->expects($this->once())
            ->method('execute')
            ->with('echo "1"')
            ->will($this->returnValue(0))
        ;
        $this->cli
            ->expects($this->any())
            ->method('getOutput')
            ->will($this->returnValue('1'))
        ;

        $task = new ShellTask($this->tempdir, $this->cli);
        $task->setOptions(array('command' => array('echo "1"')));

        $result = $task->execute('production');

        $this->assertInstanceOf('Webcreate\Conveyor\Task\Result\ExecuteResult', $result);
    }

    public function testExecuteRunsInBuilddir()
    {
        $command = 'cat "blaat.php"';

        $this->cli
            ->expects($this->once())
            ->method('execute')
            ->with($command, $this->anything(), $this->tempdir) // test the 3rd argument
            ->will($this->returnValue(0))
        ;

        $task = new ShellTask($this->tempdir, $this->cli);
        $task->setOptions(array('command' => array($command)));

        $result = $task->execute('staging');
    }
}
