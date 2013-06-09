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
use Symfony\Component\Process\Process;

use Webcreate\Conveyor\Task\Result\ExecuteResult;
use Webcreate\Util\Cli;
use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\Repository\Version;

class ShellTask extends Task
{
    protected $cwd;
    protected $cli;
    protected $io;
    protected $commandline;

    public function __construct($cwd, Cli $cli, IOInterface $io = null)
    {
        $this->cli = $cli;
        $this->io  = $io;
        $this->cwd = $cwd;
    }

    /**
     * @todo improve output (also @see SshTask)
     *
     * @param $target
     * @param  Version                                                     $version
     * @return ExecuteResult
     * @throws \Symfony\Component\Process\Exception\ProcessFailedException
     */
    public function execute($target, Version $version = null)
    {
        $this->commandline = $this->options['command'];
        foreach ($this->commandline as $command) {
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

            if ($this->cli->execute($command, $outputter, $this->cwd) <> 0) {
                throw new ProcessFailedException($this->cli->getProcess());
            }
//            elseif ($message = $this->cli->getErrorOutput()) {
//                $messages = explode("\n", $message);
//
//                $self->io->write(sprintf('<comment>%s</comment>', $messages));
//            }
        }

        return new ExecuteResult(
            $this->getDerivedFiles(),
            $this->getRemovedFiles()
        );

//        return $this->cli->getOutput();
    }

    public function simulate($target, Version $version)
    {
        $this->commandline = $this->options['command'];
        foreach ($this->commandline as $command) {
            $this->output(sprintf('Executing: <comment>%s</comment>', $command));
        }
    }

    protected function getDerivedFiles()
    {
        if (isset($this->options['creates'])) {
            return (array) $this->options['creates'];
        }

        return array();
    }

    protected function getRemovedFiles()
    {
        if (isset($this->options['removes'])) {
            return (array) $this->options['removes'];
        }

        return array();
    }
}
