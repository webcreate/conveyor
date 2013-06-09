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

use Webcreate\Conveyor\Transporter\FileTransporter;
use Webcreate\Conveyor\Test\TransporterTestCase;
use Webcreate\Conveyor\Event\TransporterEvents;

class FileTransporterTest Extends TransporterTestCase
{
    protected function createTransporter($dispatcher = null)
    {
        $transporter = new FileTransporter($dispatcher);
        $transporter
            ->setHost('localhost')
            ->setUser('jeroen')
            ->setPath($this->tmpdir)
        ;

        return $transporter;
    }

    public function setUp()
    {
        $this->tmpdir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('wbcrte');

        mkdir($this->tmpdir, 0777, true);
        file_put_contents($this->tmpdir . DIRECTORY_SEPARATOR . 'hello.txt', 'Hello?? Is it me you are looking for?');

        parent::setUp();
    }

    public function tearDown()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->tmpdir);
    }

    public function testCopyDispatchesEvent()
    {
        $this->markTestIncomplete();
    }
}
