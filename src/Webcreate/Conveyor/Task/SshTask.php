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

use Symfony\Component\Process\Exception\ProcessFailedException;

use Webcreate\Conveyor\DependencyInjection\TransporterAwareInterface;
use Webcreate\Conveyor\Repository\Version;
use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\Transporter\AbstractTransporter;
use Webcreate\Conveyor\Transporter\SshCapableTransporterInterface;
use Webcreate\Util\Cli;

class SshTask extends Task implements TransporterAwareInterface
{
    /**
     * @var AbstractTransporter
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
     * @param Version $version
     * @throws \Symfony\Component\Process\Exception\ProcessFailedException
     */
    public function execute($target, Version $version)
    {
        $command = $this->getCommand($target, $version, $this->options['command']);

        $this->output(sprintf('Executing: <comment>%s</comment>', $command));

        $hasOutput = false;

        $self = $this;
        $outputter = function ($type, $buffer) use ($self, &$hasOutput) {
            if (false === $self->io->isVerbose()) return;

            if (1 || 'out' === $type) {
                if (!$hasOutput) {
                    $this->io->write('');
                    $this->io->write('');
                    $hasOutput = true;
                }
                $self->io->write(sprintf('> %s', trim($buffer)));
            }
        };

        if ($this->cli->execute($command, $outputter) <> 0) {
            throw new ProcessFailedException($this->cli->getProcess());
        }
//        elseif ($message = $this->cli->getErrorOutput()) {
//            $this->output(sprintf('<comment>%s</comment>', $message));
//        }
    }

    public function simulate($target, Version $version)
    {
        $command = $this->getCommand($target, $version, $this->options['command']);

        $this->output(sprintf('Executing: <comment>%s</comment>', $command));
    }

    protected function getCommand($target, $version, $command)
    {
        $host = $this->transporter->getHost();
        $user = $this->transporter->getUser();
        $path = $this->transporter->getPath();

        if (isset($this->options['path']) && $this->options['path']) {
            $path = $this->options['path'];
        }

        $command = sprintf('ssh %s@%s "cd %s && %s"', $user, $host, $path, $command);

        return $command;
    }
}
