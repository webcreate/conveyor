<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Webcreate\Conveyor\Test\TransporterTestCase;
use Webcreate\Conveyor\Event\TransporterEvents;
use Webcreate\Conveyor\Transporter\FtpTransporter;

class FtpTransporterTest Extends TransporterTestCase
{
    protected function createTransporter($dispatcher = null)
    {
        $transporter = $this->getMockBuilder('Webcreate\Conveyor\Transporter\FtpTransporter')
            ->setConstructorArgs(array($dispatcher))
            ->setMethods(array('connect', 'login'))
            ->getMock()
        ;

        $transporter
            ->setHost('server.dev')
            ->setUser('jeroen')
            ->setPath('deploy')
        ;

        return $transporter;
    }

    public function testPutDispatchesEvent()
    {
        $this->markTestIncomplete();

        $this->getDispatcherMock()
            ->expects($this->once())
            ->method('dispatch')
            ->with(TransporterEvents::TRANSPORTER_PUT, $this->anything())
        ;

        $transporter = $this->createTransporter($this->getDispatcherMock());

        $tmpfile = tempnam(sys_get_temp_dir(), 'test');

        $this->transporter->put($tmpfile, $transporter->getPath() . '/deploy');
    }

    public function testMkdirDispatchesEvent()
    {
        $this->markTestIncomplete();
    }

    public function testGetDispatchesEvent()
    {
        $this->markTestIncomplete();
    }

    public function testPutContentDispatchesEvent()
    {
        $this->markTestIncomplete();
    }

    public function testSymlinkDispatchesEvent()
    {
        $this->markTestIncomplete();
    }

    public function testCopyDispatchesEvent()
    {
        $this->markTestIncomplete();
    }

    public function testRemoveDispatchesEvent()
    {
        $this->markTestIncomplete();
    }
}
