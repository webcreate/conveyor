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

class DiffCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('diff')
            ->setDescription('Compare a remote version with a local version')
            ->addArgument('target', InputArgument::REQUIRED, 'Target to diff')
            ->addArgument('version', InputArgument::REQUIRED, 'Version to diff')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $deploy = $this->getConveyor($input, $output, $this->getHelperSet());

        $deploy->diff($input->getArgument('target'), $input->getArgument('version'));
    }
}
