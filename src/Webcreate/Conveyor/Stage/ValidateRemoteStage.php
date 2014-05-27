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
use Webcreate\Conveyor\Util\FilePath;

class ValidateRemoteStage extends AbstractStage
{
    protected $transporter;
    protected $io;
    protected $subtasks = array(
        'validateRemoteBasepath',
        'validateRequiredDirectories',
        'validateRemoteVersionFile',
    );

    public function __construct($transporter, IOInterface $io, $remoteInfoFile, array $subtasks = null)
    {
        $this->transporter = $transporter;
        $this->io = $io;
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
                'Validating remote'
            )
        );

        foreach ($this->subtasks as $task) {
            if (false === $this->$task($context)) {
                return false;
            }
        }
    }

    protected function validateRemoteBasepath(Context $context)
    {
        $basepath = $this->transporter->getPath();

        if (false == $this->transporter->exists($basepath)) {
            $answer = $this->io->askConfirmation(sprintf(
                    'Directory \'%s\' does not exist, would you like to create it? (Y/n): ',
                    $basepath), true);

            if (true === $answer) {
                $this->transporter->mkdir($basepath);

                $directories = $context->getStrategy()->getRequiredDirectories();
                foreach ($directories as $directory) {
                    $this->transporter->mkdir(FilePath::join($basepath, $directory));
                }

                $context->setFullDeploy(true);
            } else {
                return false;
            }
        }
    }

    protected function validateRequiredDirectories(Context $context)
    {
        $directories = $context->getStrategy()->getRequiredDirectories();
        foreach ($directories as $directory) {
            $path = FilePath::join($this->transporter->getPath(), $directory);

            if (false == $this->transporter->exists($path)) {
                $answer = $this->io->askConfirmation(sprintf(
                        'Directory \'%s\' does not exist, would you like to create it? (Y/n): ',
                        $path), true);

                if (true === $answer) {
                    $this->transporter->mkdir($path);
                } else {
                    return false;
                }
            }
        }
    }

    protected function validateRemoteVersionFile(Context $context)
    {
        if ($context->isFullDeploy()) return;

        $versionFile = FilePath::join(
            $this->transporter->getPath(),
            $context->getStrategy()->getCurrentReleasePath(),
            $this->remoteInfoFile
        );

        if (false == $this->transporter->exists($versionFile)) {
            $answer = $this->io->askConfirmation(sprintf(
                    'Remote version information not found. Would you like to do a full deploy? (Y/n): '), 'Y');

            if ($answer) {
                $context->setFullDeploy(true);
            } else {
                return false;
            }
        }
    }
}
