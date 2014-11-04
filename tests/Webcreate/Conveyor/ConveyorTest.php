<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Webcreate\Conveyor\IO\NullIO;
use Webcreate\Conveyor\Conveyor;

class ConveyorTest extends \PHPUnit_Framework_TestCase
{
    public function testBootBuildsContainer()
    {
        $conveyor = new Conveyor();

        $this->assertNull($conveyor->getContainer());

        $conveyor->boot(new NullIO());

        $this->assertInstanceOf('Symfony\Component\DependencyInjection\ContainerInterface', $conveyor->getContainer());
    }
}
