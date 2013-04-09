<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

use Webcreate\Conveyor\Config\YamlConfig;
use Webcreate\Conveyor\IO\NullIO;
use Webcreate\Conveyor\IO\ConsoleIO;
use Webcreate\Conveyor\Conveyor;

class ConveyorTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfig()
    {
        $conveyor = new Conveyor(new NullIO());

        $this->assertInstanceOf('Webcreate\Conveyor\Config\YamlConfig', $conveyor->getConfig());
    }
}