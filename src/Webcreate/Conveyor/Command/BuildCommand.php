<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;

class BuildCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Build')
            ->addArgument('target', InputArgument::REQUIRED, 'Target to build')
            ->addArgument('version', InputArgument::REQUIRED, 'Version to build')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $deploy = $this->getConveyor($input, $output, $this->getHelperSet());

        $deploy->build($input->getArgument('target'), $input->getArgument('version'));
    }
}
