<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
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
