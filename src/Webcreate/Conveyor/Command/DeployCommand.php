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

class DeployCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('deploy')
            ->setDescription('Deploy')
            ->addArgument('target', InputArgument::REQUIRED, 'Target or group to deploy')
            ->addArgument('version', InputArgument::REQUIRED, 'Version to deploy')
            ->addOption('force', '-f', InputOption::VALUE_NONE, 'Force deploy')
            ->addOption('full', null, InputOption::VALUE_NONE, 'Full deploy instead of incremental deploy')
            ->addOption('after', null, InputOption::VALUE_NONE, 'Only run deploy after tasks')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conveyor = $this->getConveyor($input, $output, $this->getHelperSet());

        $options = array();

        $options['full_deploy'] = (bool) $input->getOption('full');
        $options['deploy_after_only'] = (bool) $input->getOption('after');
        $options['force'] = (bool) $input->getOption('force');

        $target = $input->getArgument('target');
        $targets = [$target];

        if (false !== strpos($target, ',')) {
            $output->writeln('<info>Deprecated: Comma-separated list of targets is no longer supported, please use target groups to deploy to multiple targets at once.</info>');

            $targets = explode(',', $target);
        }

        foreach ($targets as $target) {
            $conveyor->deploy($target, $input->getArgument('version'), $options);
        }
    }
}
