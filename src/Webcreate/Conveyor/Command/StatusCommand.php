<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class StatusCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('status')
            ->setDescription('Show status for each target')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $deploy = $this->getConveyor($input, $output, $this->getHelperSet());

        $status = $deploy->status();

        if (count($status) > 0) {
            $output->writeln('<comment>Available targets:</comment>');

            // calculate max string length of targets
            $maxStrlen = max(array_map(function ($item) { return strlen($item); }, array_keys($status)));

            foreach ($status as $target => $info) {
                $abortMessage = null;

                if (isset($info['error'])) {
                    $abortMessage = sprintf('<error>Error: %s</error>', $info['error']->getMessage());
                } elseif (false === $info) {
                    $abortMessage = 'Not deployed';
                }

                if (null !== $abortMessage) {
                    $output->writeln(
                        sprintf(
                            '  <info>%-'.($maxStrlen + 2).'s</info> %s',
                            $target,
                            $abortMessage
                        )
                    );

                    continue;
                }

                $version = $info['remoteVersion'];
                $localVersion = $info['localVersion'];
                $versionCompare = $info['compare'];

                $statusTxt = '';
                if (0 === $versionCompare) {
                    $statusTxt = 'Up-to-date';
                } elseif (1 === $versionCompare) {
                    $statusTxt = sprintf('Behind by %d commit(s) on %s', count($info['changelog']), $localVersion->getName());
                } elseif (-1 === $versionCompare) {
                    $statusTxt = sprintf('Ahead by %d commit(s) on %s', count($info['changelog']), $localVersion->getName());
                }

                $output->writeln(
                    sprintf(
                        '  <info>%-'.($maxStrlen + 2).'s</info> <info>%s</info> (<comment>%s</comment>) %s',
                        $target,
                        $version->getName(),
                        $version->getBuild(),
                        $statusTxt
                    )
                );
            }

            $baseVersion = 'dev-' . $deploy->getRepository()->getMasterBranch();

            $output->writeln('');
            $output->writeln(sprintf('Run `%s diff <target> %s` to view changed files', basename($_SERVER['argv'][0]), $baseVersion));
        } else {
            $output->writeln('No targets defined');
        }
    }
}
