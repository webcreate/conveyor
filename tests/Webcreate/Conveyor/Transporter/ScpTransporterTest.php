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
