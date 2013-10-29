<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Repository\Driver;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Vcs\Svn;

class SvnDriver extends AbstractVcsDriver
{
    protected $dispatcher;

    public function __construct($url, IOInterface $io = null, EventDispatcherInterface $dispatcher = null)
    {
        $this->url        = $url;
        $this->io         = $io;
        $this->dispatcher = $dispatcher;
    }

    protected function getClient($url)
    {
        $client = new Svn($url);
        $client->getAdapter()->setExecutable('svn');

        if (null !== $this->dispatcher) {
            $client->setDispatcher($this->dispatcher);
        }

        return $client;
    }
}
