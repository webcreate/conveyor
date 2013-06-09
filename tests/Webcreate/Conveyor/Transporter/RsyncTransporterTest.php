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
use Webcreate\Util\Cli;
use Webcreate\Conveyor\Transporter\RsyncTransporter;

class RsyncTransporterTest Extends TransporterTestCase
{
    protected function createTransporter($dispatcher, $cli = null, $methods = array('exists'))
    {
        $cli = $cli ?: $this->getMock('Webcreate\\Util\\Cli');
        $transporter = $this->getMockBuilder('Webcreate\Conveyor\Transporter\RsyncTransporter')
            ->setConstructorArgs(array($dispatcher, $cli))
            ->setMethods($methods)
            ->getMock()
        ;

        $transporter
            ->expects($this->any())
            ->method('exists')
            ->will($this->returnValue(true))
        ;

        $transporter
            ->setHost('server.dev')
            ->setUser('jeroen')
            ->setPath('deploy')
        ;

        return $transporter;
    }

    public function setUp()
    {
        $this->cli = $this->getMock('Webcreate\\Util\\Cli');
        $this->dispatcher = $this->getDispatcherMock();
        $this->transporter = $this->createTransporter($this->dispatcher, $this->cli);
    }

    public function testExists()
    {
        $this->transporter = $this->createTransporter($this->getDispatcherMock(), $this->cli, null);

        $this->cli
            ->expects($this->once())
            ->method('execute')
            ->with('ssh jeroen@server.dev "test -e bin/composer.phar"')
            ->will($this->returnValue(0))
        ;

        $result = $this->transporter->exists('bin/composer.phar');

        $this->assertEquals(true , $result);
    }

    public function testMkdir()
    {
        $this->cli
            ->expects($this->at(0))
            ->method('execute')
            ->with('ssh jeroen@server.dev "mkdir \'deploy\'"')
            ->will($this->returnValue(0))
        ;

        $this->transporter->mkdir('deploy', false);
    }

    public function testGet()
    {
        $this->cli
            ->expects($this->once())
            ->method('execute')
            ->with('rsync jeroen@server.dev:deploy "/tmp/test"')
            ->will($this->returnValue(0))
        ;

        $this->transporter->get('deploy', '/tmp/test');
    }

    public function testPut()
    {
        $tmpfile = tempnam(sys_get_temp_dir(), 'test');

        $this->cli
            ->expects($this->once())
            ->method('execute')
            ->with(sprintf('rsync -az "%s" \'jeroen@server.dev:deploy\'', $tmpfile))
            ->will($this->returnValue(0))
        ;

        $this->transporter->put($tmpfile, 'deploy');
    }
}
