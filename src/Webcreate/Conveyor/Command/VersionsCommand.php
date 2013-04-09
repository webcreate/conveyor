<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\Command;

class VersionsCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('versions')
            ->setDescription('List available versions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $deploy = $this->getConveyor($input, $output, $this->getHelperSet());

        $versions = $deploy->getVersions();

        $output->writeln('<comment>Available versions:</comment>');

        foreach ($versions as $version) {
            $output->writeln(sprintf('  <info>%s</info> (<comment>%s</comment>)', $version->getName(), $version->getBuild()));
        }

        $output->writeln('');
    }
}
