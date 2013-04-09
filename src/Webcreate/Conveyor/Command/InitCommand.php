<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Command;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;

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
