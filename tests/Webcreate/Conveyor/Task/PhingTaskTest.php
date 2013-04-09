<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

use Webcreate\Conveyor\Task\PhingTask;

class PhingTaskTest extends \PHPUnit_Framework_TestCase
{
    protected $cli;

    public function setUp()
    {
        $this->cli = $this->getMockBuilder('Webcreate\\Util\\Cli')
            ->setMethods(array('execute', 'getErrorOutput'))
            ->getMock()
        ;
    }

    public function testExecuteForSinglePhingTarget()
    {
        $this->cli
            ->expects($this->once())
            ->method('execute')
            ->with('phing \'firstTarget\'', null, '/tmp')
            ->will($this->returnValue(0))
        ;

        $task = new PhingTask('/tmp', $this->cli);
        $task->setOptions(array(
            'target' => array('firstTarget')
        ));

        $task->execute('target1');
    }

    public function testExecuteForMultiplePhingTarget()
    {
        $this->cli
            ->expects($this->once())
            ->method('execute')
            ->with('phing \'firstTarget\' \'secondTarget\' \'third\'', null, '/tmp')
            ->will($this->returnValue(0))
        ;

        $task = new PhingTask('/tmp', $this->cli);
        $task->setOptions(array(
            'target' => array('firstTarget', 'secondTarget', 'third')
        ));

        $task->execute('target1');
    }

    public function testExecuteForNonDefaultBuildFile()
    {
        $this->cli
            ->expects($this->once())
            ->method('execute')
            ->with('phing -f \'hello.xml\' \'firstTarget\'', null, '/tmp')
            ->will($this->returnValue(0))
        ;

        $task = new PhingTask('/tmp', $this->cli);
        $task->setOptions(array(
            'buildfile' => 'hello.xml',
            'target' => array('firstTarget')
        ));

        $task->execute('target1');
    }
}
