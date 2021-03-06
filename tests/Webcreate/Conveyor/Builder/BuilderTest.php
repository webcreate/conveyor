<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Webcreate\Conveyor\Builder\Builder;

class BuilderTest extends \PHPUnit_Framework_TestCase
{
    protected $tempdir;

    public function setUp()
    {
        $this->tempdir = sys_get_temp_dir();
    }

    public function testBuilderCallsExecuteOnTasks()
    {
        $task1 = $this->getMockBuilder('Webcreate\Conveyor\Task\Task')->disableOriginalConstructor()->getMock();
        $task1
            ->expects($this->once())
            ->method('execute')
            ->withAnyParameters()
        ;
        $task1
            ->expects($this->any())
            ->method('supports')
            ->will($this->returnValue(true))
        ;

        $version = $this->getMockBuilder('Webcreate\Conveyor\Repository\Version')->getMock();

        $builder = new Builder($this->tempdir, array($task1), new \Webcreate\Conveyor\IO\NullIO());
        $builder->build('test', $version);
    }

    public function testBuildTriggersBuildEvents()
    {
        $dispatcher = $this->getMockBuilder('\Symfony\Component\EventDispatcher\EventDispatcher')->getMock();
        $dispatcher
            ->expects($this->at(0))
            ->method('dispatch')
            ->with($this->equalTo('builder.preBuild'))
        ;
        $dispatcher
            ->expects($this->at(1))
            ->method('dispatch')
            ->with($this->equalTo('builder.preTask'))
        ;
        $dispatcher
            ->expects($this->at(2))
            ->method('dispatch')
            ->with($this->equalTo('builder.postTask'))
        ;
        $dispatcher
            ->expects($this->at(3))
            ->method('dispatch')
            ->with($this->equalTo('builder.postBuild'))
        ;

        $task1 = $this->getMockBuilder('Webcreate\Conveyor\Task\Task')->disableOriginalConstructor()->getMock();
        $task1
            ->expects($this->any())
            ->method('supports')
            ->will($this->returnValue(true))
        ;

        $builder = new Builder($this->tempdir, array($task1), new \Webcreate\Conveyor\IO\NullIO(), $dispatcher);
        $builder->build('test', new \Webcreate\Conveyor\Repository\Version('dev-master', 'abc123'));
    }

    public function testGetBuilddir()
    {
        $builder = new Builder($this->tempdir, array(), new \Webcreate\Conveyor\IO\NullIO());

        $this->assertEquals($this->tempdir, $builder->getBuildDir());
    }
}
