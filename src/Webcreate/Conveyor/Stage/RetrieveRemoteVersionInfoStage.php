<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Stage;

use Webcreate\Conveyor\Context;
use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\Repository\Repository;
use Webcreate\Conveyor\Repository\Version;
use Webcreate\Conveyor\Util\FilePath;

class RetrieveRemoteVersionInfoStage extends AbstractStage
{
    protected $transporter;
    protected $repository;
    protected $io;
    protected $subtasks = array(
                'getRemoteVersion',
                'compareRemoteVersionName',
                'compareRemoteVersionBuild',
    );

    public function __construct($transporter, Repository $repository, IOInterface $io, $remoteInfoFile, array $subtasks = null)
    {
        $this->transporter    = $transporter;
        $this->repository     = $repository;
        $this->io             = $io;
        $this->remoteInfoFile = $remoteInfoFile;

        if (null !== $subtasks) {
            $this->subtasks = $subtasks;
        }
    }

    public function supports(Context $context)
    {
        return true;
    }

    public function execute(Context $context)
    {
        $this->io->write(
            sprintf(
                'Retrieving remote version information'
            )
        );

        foreach ($this->subtasks as $task) {
            if (false === $this->$task($context)) {
                return false;
            }
        }
    }

    protected function getRemoteVersion(Context $context)
    {
        if ($context->isFullDeploy()) return;

        $versionFile = FilePath::join(
            $this->transporter->getPath(),
            $context->getStrategy()->getCurrentReleasePath(),
            $this->remoteInfoFile
        );
        $content = $this->transporter->get($versionFile);

        list($name, $build) = explode(':', trim($content));
        $context->setRemoteVersion(new Version($name, $build));
    }

    protected function compareRemoteVersionName(Context $context)
    {
        if ($context->isFullDeploy()) return;

        if ($context->getVersion()->getName() != $context->getRemoteVersion()->getName()) {
            $answer = $this->io->askConfirmation(sprintf(
                    'Remote version (%s) differs from selected version (%s). '.
                    'Would you like to continue? (Y/n): ',
                    $context->getRemoteVersion()->getName(),
                    $context->getVersion()->getName()), 'Y');

            if (false === $answer) {
                return false;
            }
        }
    }

    protected function compareRemoteVersionBuild(Context $context)
    {
        if ($context->isFullDeploy()) return;

        if ($context->getVersion()->equals($context->getRemoteVersion())) {
            $this->io->write('Remote version is already up-to-date.', true);

            return false;
        } elseif (1 === $this->repository->versionCompare($context->getRemoteVersion(), $context->getVersion())) {
            $answer = $this->io->askConfirmation(sprintf(
                    'Remote version (%s) is newer than the selected version (%s). ' .
                    'Would you like to continue as full deploy? (Y/n): ',
                    $context->getRemoteVersion()->getBuild(),
                    $context->getVersion()->getBuild()), 'Y');

            if ($answer) {
                $context->setFullDeploy(true);
            } else {
                return false;
            }
        }
    }
}
