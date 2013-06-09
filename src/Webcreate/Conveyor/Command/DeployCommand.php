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

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;

class DeployCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('deploy')
            ->setDescription('Deploy')
            ->addArgument('target', InputArgument::REQUIRED, 'Target to simulate')
            ->addArgument('version', InputArgument::REQUIRED, 'Version to simulate')
            ->addOption('full', 'F', InputOption::VALUE_NONE, 'Force full deploy instead of incremental deploy')
            ->addOption('after', null, InputOption::VALUE_NONE, 'Only run deploy after tasks')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $deploy = $this->getConveyor($input, $output, $this->getHelperSet());

        $options = array();

        if ($input->getOption('full')) {
            $options['full_deploy'] = true;
        }

        if ($input->getOption('after')) {
            $options['deploy_after_only'] = true;
        }

        $deploy->deploy($input->getArgument('target'), $input->getArgument('version'), $options);
    }
}
