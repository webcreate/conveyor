<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Stage;

use Webcreate\Conveyor\Repository\Repository;
use Webcreate\Vcs\Common\Status;
use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\Context;

class DiffStage extends AbstractStage
{
    public function __construct(Repository $repository, IOInterface $io)
    {
        $this->repository = $repository;
        $this->io = $io;
    }

    public function supports(Context $context)
    {
        return (null !== $context->getRemoteVersion());
    }

    public function execute(Context $context)
    {
        $this->io->write(
                sprintf(
                    'Comparing <info>%s</info> (<comment>%s</comment>) with <info>%s</info> (<comment>%s</comment>)',
                        $context->getVersion()->getName(),
                        $context->getVersion()->getBuild(),
                        $context->getRemoteVersion()->getName(),
                        $context->getRemoteVersion()->getBuild()
                )
        );

        $result = $this->repository->diff($context->getRemoteVersion(), $context->getVersion());

        if ($result) {
            foreach ($result as $fileinfo) {
                switch ($fileinfo->getStatus()) {
                    case Status::ADDED:
                        $symbol = '+';
                        $color = 'info';
                        break;
                    case Status::MODIFIED:
                        $symbol = '*';
                        $color = 'comment';
                        break;
                    case Status::DELETED:
                        $symbol = '-';
                        $color = 'error';
                        break;
                }

                $this->io->write(sprintf(' <%s>%-2s %s</%s>', $color, $symbol, $fileinfo->getPathname(), $color));
            }
        } else {
            $this->io->write('No changes between versions');
        }
    }
}
