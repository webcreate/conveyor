<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\Command;

class ValidateCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('validate')
            ->setDescription('Validate configuration')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $deploy = $this->getConveyor($input, $output, $this->getHelperSet());

        $result = $deploy->validate();

        if (true === $result) {
            $output->writeln(sprintf('%s is valid', $deploy->getConfig()->getFile()));
        }
    }
}
