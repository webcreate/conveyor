<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Stage;

use Webcreate\Conveyor\IO\IOInterface;

use Webcreate\Conveyor\Transporter\AbstractTransporter;
use Webcreate\Conveyor\Context;
use Webcreate\Conveyor\Stage\AbstractStage;
use Webcreate\Conveyor\Util\FilePath;

class WriteRemoteInfoFileStage extends AbstractStage
{
    protected $transporter;
    protected $remoteInfoFile;
    protected $io;

    public function __construct(AbstractTransporter $transporter, $remoteInfoFile, IOInterface $io = null)
    {
        $this->transporter = $transporter;
        $this->remoteInfoFile = $remoteInfoFile;
        $this->io = $io;
    }

    public function supports(Context $context)
    {
        return (false === $context->isSimulate());
    }

    public function execute(Context $context)
    {
        if ($this->io) {
            $this->io->write(sprintf('Wring remote info file'));
        }

        $version  = $context->getVersion();

        $versionFile = FilePath::join(
            $this->transporter->getPath(),
            $context->getStrategy()->getUploadPath($context->getVersion()),
            $this->remoteInfoFile
        );

        $this->transporter->putContent($version->getUID(), $versionFile);
    }
}
