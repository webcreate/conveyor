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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class InitCommand extends AbstractCommand
{
    protected function configure()
    {
        $yml = Yaml::parse(file_get_contents(__DIR__ . '/../Resources/config/parameters.yml'));

        $this
            ->setName('init')
            ->setDescription('Initialise a Conveyor configuration file')
            ->addArgument('filename', InputArgument::OPTIONAL, 'Alternative filename.', $yml['parameters']['conveyor.configfile'])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $deploy = $this->getConveyor($input, $output, $this->getHelperSet());

        $filename = $input->getArgument('filename');

        $deploy->init($filename);
    }
}
