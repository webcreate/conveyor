<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

use Webcreate\Conveyor\IO\NullIO;
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
        $this->getDispatcherMock()
            ->expects($this->once())
            ->method('dispatch')
            ->with(TransporterEvents::TRANSPORTER_PUT, $this->anything())
        ;

        $transporter = $this->createTransporter($this->getDispatcherMock());

        $tmpfile = tempnam(sys_get_temp_dir(), 'test');

        $this->transporter->put($tmpfile, $transporter->getPath() . '/deploy');
    }
}
