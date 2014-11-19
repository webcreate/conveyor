<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UndeployCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('undeploy')
            ->setDescription('Undeploy (use with care!)')
            ->addArgument('target', InputArgument::REQUIRED, 'Target or group to undeploy')
            ->addOption('force', '-f', InputOption::VALUE_NONE, 'Force undeploy')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conveyor = $this->getConveyor($input, $output, $this->getHelperSet());

        if (!$input->getOption('force')) {
            $answer = $conveyor->getIO()->askConfirmation(
                sprintf(
                    'Are you sure you want to undeploy? The remote directory "%s" will be removed! ' .
                    'Data will be lost! (y/N): ',
                    $conveyor->getTransporter($input->getArgument('target'))->getPath()
                ),
                false
            );

            if (!$answer) {
                return;
            }
        }

        $options = array();

        $conveyor->undeploy($input->getArgument('target'), $options);
    }
}
