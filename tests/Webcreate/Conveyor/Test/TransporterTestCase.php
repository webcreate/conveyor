<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Test;

use Webcreate\Conveyor\Event\TransporterEvents;
use Webcreate\Conveyor\Transporter\AbstractTransporter;

abstract class TransporterTestCase extends \PHPUnit_Framework_TestCase
{
    protected $dispatcher;

    /**
     * @var AbstractTransporter
     */
    protected $transporter;

    public function setUp()
    {
        $this->dispatcher = $this->getDispatcherMock();
        $this->transporter = $this->createTransporter($this->dispatcher);
    }

    /**
     * @param $dispatcher
     * @return AbstractTransporter
     */
    abstract protected function createTransporter($dispatcher);

    protected function getDispatcherMock()
    {
        if (null === $this->dispatcher) {
            $this->dispatcher = $this->getMock('Symfony\\Component\\EventDispatcher\\EventDispatcherInterface');
        }

        return $this->dispatcher;
    }

    public function testMkdirDispatchesEvent()
    {
        $this->getDispatcherMock()
            ->expects($this->once())
            ->method('dispatch')
            ->with(TransporterEvents::TRANSPORTER_MKDIR, $this->anything())
        ;

        $this->transporter->mkdir($this->transporter->getPath() . '/deploy');
    }

    public function testGetDispatchesEvent()
    {
        $this->getDispatcherMock()
            ->expects($this->once())
            ->method('dispatch')
            ->with(TransporterEvents::TRANSPORTER_GET, $this->anything())
        ;

        $this->transporter->get($this->transporter->getPath() . '/hello.txt', '/tmp/test');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPutThrowsExceptionWhenFileDoesnotExist()
    {
        $this->transporter->put($this->transporter->getPath() . '/non/existing/file', 'destination');
    }

    public function testPutContentDispatchesEvent()
    {
        $this->getDispatcherMock()
            ->expects($this->once())
            ->method('dispatch')
            ->with(TransporterEvents::TRANSPORTER_PUT_CONTENT, $this->anything())
        ;

        $tmpcontent = "hello world";

        $this->transporter->putContent($tmpcontent, $this->transporter->getPath() . '/deploy');
    }

    public function testSymlinkDispatchesEvent()
    {
        $this->getDispatcherMock()
            ->expects($this->once())
            ->method('dispatch')
            ->with(TransporterEvents::TRANSPORTER_SYMLINK, $this->anything())
        ;

        $this->transporter->symlink($this->transporter->getPath() . '/a/path', $this->transporter->getPath() . '/deploy');
    }

    public function testCopyDispatchesEvent()
    {
        $this->getDispatcherMock()
            ->expects($this->once())
            ->method('dispatch')
            ->with(TransporterEvents::TRANSPORTER_COPY, $this->anything())
        ;

        $this->transporter->copy($this->transporter->getPath() . '/a/path', $this->transporter->getPath() . '/deploy');
    }

    public function testRemoveDispatchesEvent()
    {
        $this->getDispatcherMock()
            ->expects($this->once())
            ->method('dispatch')
            ->with(TransporterEvents::TRANSPORTER_REMOVE, $this->anything())
        ;

        $this->transporter->remove($this->transporter->getPath() . '/a/path');
    }
}
