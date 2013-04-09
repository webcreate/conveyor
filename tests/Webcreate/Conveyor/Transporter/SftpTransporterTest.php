<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

use Webcreate\Conveyor\IO\NullIO;
use Webcreate\Conveyor\Test\TransporterTestCase;
use Webcreate\Conveyor\Transporter\SftpTransporter;

require __DIR__ . "/../../../../vendor/phpseclib/phpseclib/phpseclib/Net/SFTP.php";

class SftpTransporterTest Extends TransporterTestCase
{
    protected function createTransporter($dispatcher = null)
    {
        $sftp = $this
            ->getMockBuilder('Webcreate\Conveyor\Transporter\Ftp\Sftp')
            ->setMethods(array('isConnected', 'chdir'))
            ->getMock()
        ;

        $sftp
            ->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue(true))
        ;

        $sftp
            ->expects($this->any())
            ->method('chdir')
            ->will($this->returnValue(true))
        ;

        $transporter = new SftpTransporter($dispatcher, $sftp, new NullIO());
        $transporter
            ->setHost('server.dev')
            ->setUser('jeroen')
            ->setPath('deploy')
        ;

        return $transporter;
    }
}
