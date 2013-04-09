<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

use Webcreate\Conveyor\IO\NullIO;
use Webcreate\Conveyor\Test\TransporterTestCase;
use Webcreate\Conveyor\Event\TransporterEvents;
use Webcreate\Conveyor\Transporter\ScpTransporter;

class ScpTransporterTest Extends TransporterTestCase
{
    protected $cli;

    public function setUp()
    {
        $this->cli = $this->getMock('Webcreate\\Util\\Cli');

        parent::setUp();
    }

    protected function createTransporter($dispatcher = null)
    {
        $transporter = new ScpTransporter($dispatcher, $this->cli);
        $transporter
            ->setHost('server.dev')
            ->setUser('jeroen')
            ->setPath('deploy')
        ;

        return $transporter;
    }

    public function testMkdirDispatchesEvent()
    {
        $this->markTestIncomplete();
    }
}
