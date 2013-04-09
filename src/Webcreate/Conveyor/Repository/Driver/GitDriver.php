<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Repository\Driver;

use Webcreate\Conveyor\Repository\Version;
use Webcreate\Vcs\Git;

class GitDriver extends AbstractVcsDriver
{
    protected function getClient($url)
    {
        $client = new Git($url);
        $client->getAdapter()->setExecutable('git');

        return $client;
    }

    /**
     * FIXME passing `null`, `null` failed on svn client, but looks like
     *       it's needed by the git client. Fix the git client to accept
     *       Reference's correctly!
     *
     * @param Version $oldVersion
     * @param Version $newVersion
     * @return mixed
     */
    public function diff(Version $oldVersion, Version $newVersion)
    {
        $head = $this->getHead($oldVersion);
        $this->client->setHead($head);

        $result = $this->client->diff(
            null, null,
            $oldVersion->getBuild(), $newVersion->getBuild()
        );

        return $result;
    }
}
