<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Task;

use Webcreate\Conveyor\DependencyInjection\TransporterAwareInterface;
use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\Repository\Version;
use Webcreate\Conveyor\Transporter\AbstractTransporter;
use Webcreate\Conveyor\Transporter\SshCapableTransporterInterface;
use Webcreate\Conveyor\Util\FilePath;
use Webcreate\Util\Cli;

class SshTask extends Task implements TransporterAwareInterface
{
    /**
     * @var AbstractTransporter|SshCapableTransporterInterface
     */
    protected $transporter;

    public function __construct($config, Cli $cli, IOInterface $io)
    {
        $this->config = $config;
        $this->cli    = $cli;
        $this->io     = $io;
    }

    public function setTransporter($transporter)
    {
        if (false === $transporter instanceof SshCapableTransporterInterface) {
            throw new \InvalidArgumentException(sprintf("Given transporter of type '%s' does not support SSH.", get_class($transporter)));
        }

        $this->transporter = $transporter;
    }

    /**
     * @todo improve output (also @see ShellTask)
     *
     * @param $target
     * @param  Version $version
     * @throws \RuntimeException
     */
    public function execute($target, Version $version)
    {
        $command = $this->getCommand($target, $version, $this->options['command']);

        $this->output(sprintf('Executing: <comment>%s</comment>', $command));

        $hasOutput = false;

        $self = $this;
        $outputter = function ($buffer) use ($self, &$hasOutput) {
            if (false === $self->io->isVerbose()) return;

            if (!$hasOutput) {
                $this->io->write('');
                $this->io->write('');
                $hasOutput = true;
            }

            $lines = explode("\n", $buffer);
            foreach ($lines as $line) {
                if ($output = trim($line, "\r\n")) {
                    $self->io->write(sprintf('> %s', $output));
                }
            }
        };

        if ($exitCode = $this->transporter->exec($command, $outputter) <> 0) {
            throw new \RuntimeException(
                sprintf(
                    'The command "%s" failed.'."\nExit Code: %s",
                    $command,
                    $exitCode
                )
            );
        }
    }

    public function simulate($target, Version $version)
    {
        $command = $this->getCommand($target, $version, $this->options['command']);

        $this->output(sprintf('Executing: <comment>%s</comment>', $command));
    }

    protected function getCommand($target, $version, $command)
    {
        $basepath = $path = $this->transporter->getPath();

        if (isset($this->options['path']) && $this->options['path']) {
            $path = FilePath::join($basepath, $this->options['path']);
        }

        $command = sprintf('cd %s && %s', $path, $command);

        return $command;
    }
}
