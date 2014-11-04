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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
