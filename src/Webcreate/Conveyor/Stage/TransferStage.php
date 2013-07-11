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

use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\Context;
use Webcreate\Conveyor\Repository\Version;
use Webcreate\Conveyor\Strategy\StrategyInterface;
use Webcreate\Conveyor\Transporter\AbstractTransporter;
use Webcreate\Conveyor\Util\FileCollection;
use Webcreate\Conveyor\Util\FilePath;

class TransferStage extends AbstractStage
{
    protected $showProgress = false;

    public function __construct(AbstractTransporter $transporter, IOInterface $io)
    {
        $this->transporter = $transporter;
        $this->io = $io;
    }

    public function supports(Context $context)
    {
        return (count($context->getFilesModified()) > 0);
    }

    public function execute(Context $context)
    {
        $filesModified = $context->getFilesModified();
        $filesDeleted = $context->getFilesDeleted();

        if ($this->io) {
            $this->io->write('');

            if (true === $context->isSimulate()) {
                $this->io->write(sprintf('Simulating upload to <info>%s</info>', $context->getTarget()));
            } else {
                $this->io->write(sprintf('Uploading to <info>%s</info>', $context->getTarget()));
            }

            $this->io->write(sprintf(' - Uploading to <comment>%s</comment>', $this->transporter->getHost()));
            $this->io->increaseIndention(3);
        }

        $this->uploadFiles($filesModified->toArray(), $context);
        $this->removeFiles($filesDeleted->toArray(), $context);

        if ($this->io) {
            if (true === $this->showProgress) {
                // clear last line, because it was a overwrite
                $this->io->write('');
            }

            $this->io->decreaseIndention(3);
        }
    }

    protected function uploadFiles(array $files, Context $context)
    {
        $total = count($files);

        foreach ($files as $t => $file) {
            $src = FilePath::join($context->getBuilddir(), $file);
            $dest = FilePath::join($this->getDestinationPath($context->getStrategy(), $context->getVersion()), $file);

            if (true === file_exists($src)) {
                if (true === is_dir($src)) {
                    if (false === $this->transporter->exists($dest)) {
                        $this->transporter->mkdir($dest);
                    }
                } else {
                    $this->transporter->put($src, $dest);
                }
            } else {
                // @todo we might wanna ask the user if he likes to continue or abort
                if ($this->io && false === $this->showProgress) {
                    $this->io->write(sprintf('Warning! <comment>%s</comment> not found', $src));
                }
            }

            if ($this->io && true === $this->showProgress) {
                $this->io->overwrite(sprintf('Progress: <comment>%d%%</comment>', (($t + 1) * 100) / $total), false);
            }
        }
    }

    protected function removeFiles(array $files, Context $context)
    {
        $total = count($files);

        foreach ($files as $t => $file) {
            $src = FilePath::join($context->getBuilddir(), $file);
            $dest = FilePath::join($this->getDestinationPath($context->getStrategy(), $context->getVersion()), $file);

            $this->transporter->remove($dest, true);

            if ($this->io && true === $this->showProgress) {
                $this->io->overwrite(sprintf('Progress: <comment>%d%%</comment>', (($t + 1) * 100) / $total), false);
            }
        }
    }

    protected function getDestinationPath(StrategyInterface $strategy, Version $version)
    {
        return FilePath::join(
            $this->transporter->getPath(),
            $strategy->getUploadPath($version)
        );
    }
}
