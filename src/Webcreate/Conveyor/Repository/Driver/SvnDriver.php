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

use Webcreate\Conveyor\Repository\Repository;
use Webcreate\Vcs\Svn;

class SvnDriver extends AbstractVcsDriver
{
    protected function getClient($url)
    {
        $client = new Svn($url);
        $client->getAdapter()->setExecutable('svn');

        return $client;
    }
}
