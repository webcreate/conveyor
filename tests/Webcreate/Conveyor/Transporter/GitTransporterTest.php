<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Filesystem\Filesystem;
use Webcreate\Conveyor\Test\TransporterTestCase;

class GitTransporterTest Extends TransporterTestCase
{
    protected $tmpdir;

    public function setUp()
    {
        $this->tmpdir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('userdir');

        mkdir($this->tmpdir, 0777, true);

        parent::setUp();
    }

    public function tearDown()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->tmpdir);
    }

    public function createTransporter($dispatcher)
    {
        $tmpdir = $this->tmpdir;

        $transporterMock = $this->getMockBuilder('Webcreate\Conveyor\Transporter\GitTransporter')
            ->setConstructorArgs(array($dispatcher, $tmpdir))
            ->setMethods(array('checkout'))
            ->getMock()
        ;

        $transporterMock
            ->expects($this->any())
            ->method('checkout')
        ;

        $transporterMock->setOptions(array('url' => 'http://fakehost/repos.git'));

        // fake a checkout
        mkdir($transporterMock->getPath(), 0777, true);
        file_put_contents($transporterMock->getPath() . '/hello.txt', 'world');

        return $transporterMock ; //new Webcreate\Conveyor\Transporter\GitTransporter();
    }

    public function testSymlinkDispatchesEvent()
    {
        $this->markTestIncomplete();
    }

    public function testCopyDispatchesEvent()
    {
        $this->markTestIncomplete();
    }
}
