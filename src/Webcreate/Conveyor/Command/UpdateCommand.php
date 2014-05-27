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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webcreate\Conveyor\Command\AbstractCommand;

class UpdateCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Updates conveyor to the latest version')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force update of latest version')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dsn     = 'http://conveyordeploy.com';
        $latest  = trim(file_get_contents(sprintf('%s/version', $dsn)));
        $current = trim(file_get_contents('phar://conveyor.phar/VERSION'));
        $update  = version_compare($current, $latest, '<');

        if ($update || $input->getOption('force')) {
            $remoteFile = sprintf('%s/conveyor.phar', $dsn);
            $localFile  = $_SERVER['argv'][0];
            $tempFile   = tempnam(sys_get_temp_dir(), 'conveyor') . '.phar';

            file_put_contents($tempFile, file_get_contents($remoteFile));

            try {
                chmod($tempFile, 0777 & ~umask());
                // test the phar validity
                $phar = new \Phar($tempFile);
                // free the variable to unlock the file
                unset($phar);
                rename($tempFile, $localFile);

                $output->writeln(sprintf("<info>Conveyor has been updated to %s.</info>", $latest));
            } catch (\Exception $e) {
                @unlink($tempFile);
                if (!$e instanceof \UnexpectedValueException && !$e instanceof \PharException) {
                    throw $e;
                }
                $output->writeln('<error>The download is corrupted ('.$e->getMessage().').</error>');
                $output->writeln('<error>Please re-run the update command to try again.</error>');
            }
        } else {
            $output->writeln("<info>You are using the latest version of Conveyor.</info>");
        }
    }
}
