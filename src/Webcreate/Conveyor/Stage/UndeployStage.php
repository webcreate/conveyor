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
use Webcreate\Conveyor\Transporter\AbstractTransporter;

class UndeployStage extends AbstractStage
{
    /**
     * @var AbstractTransporter
     */
    protected $transporter;

    /**
     * @var IOInterface
     */
    protected $io;

    public function __construct(AbstractTransporter $transporter, IOInterface $io)
    {
        $this->transporter = $transporter;
        $this->io = $io;
    }

    public function supports(Context $context)
    {
        // check if directory exists
        return $this->transporter->exists($this->transporter->getPath());
    }

    public function execute(Context $context)
    {
        if (!$context->isForce()) {
            $answer = $this->io->askConfirmation(
                sprintf(
                    'Are you sure you want to remove the remote directory "%s"? Data will be lost!' .
                    ' (y/N): ',
                    $this->transporter->getPath()
                ),
                false
            );

            if (!$answer) {
                return;
            }
        }

        $this->transporter->remove($this->transporter->getPath());
    }
}
