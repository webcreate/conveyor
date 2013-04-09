<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Task;

use Webcreate\Conveyor\DependencyInjection\TransporterAwareInterface;
use Webcreate\Conveyor\IO\IOInterface;

class TaskRunner implements TransporterAwareInterface
{
    protected $tasks = array();
    protected $needsNewline = false;
    protected $transporter;

    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    public function addTask(Task $task)
    {
        $this->tasks[] = $task;
    }

    public function insertTask(Task $task)
    {
        array_unshift($this->tasks, $task);
    }

    public function hasTasks()
    {
        return (count($this->tasks) > 0);
    }

    /**
     * @return Task[]
     */
    public function getTasks()
    {
        return $this->tasks;
    }

    public function setTransporter($transporter)
    {
        $this->transporter = $transporter;

        // apply it also to the tasks
        array_walk(
            $this->tasks,
            function ($task) use ($transporter) {
                if ($task instanceof TransporterAwareInterface) {
                    $task->setTransporter($transporter);
                }
            }
        );
    }

    public function execute($target, $version)
    {
        $io = $this->io;

        foreach ($this->tasks as $i => $task) {
            $this->executeTaskWithErrorHandling($task, $i, $target, $version);
        }

        if (true === $this->needsNewline) {
            $this->io->write('');
            $this->needsNewline = false;
        }
    }

    public function simulate($target, $version)
    {
        $io = $this->io;

        foreach ($this->tasks as $i => $task) {
            if ($i > 0) {
                if (true === $this->needsNewline) {
                    $this->io->write('');
                    $this->needsNewline = false;
                }
                $this->io->write('');
            }

            $this->io->write(sprintf('- Simulating task <info>%s</info>', get_class($task)));
            $this->io->increaseIndention(2);

            $self = $this;

            $task->setOutput(function($output) use ($io, $self) {
                $io->overwrite(sprintf('%s', $output), false);

                $self->needsNewline = true;
            });

            $task->simulate($target, $version);

            $this->io->decreaseIndention(2);
        }

        if (true === $this->needsNewline) {
            $this->io->write('');
            $this->needsNewline = false;
        }
    }

    protected function executeTaskWithErrorHandling(Task $task, $i, $target, $version)
    {
        $io = $this->io;

        if ($i > 0) {
            if (true === $this->needsNewline) {
                $this->io->write('');
                $this->needsNewline = false;
            }
            $this->io->write('');
        }

        while (true) {
            $this->io->write(sprintf('- Executing task <info>%s</info>', get_class($task)));
            $this->io->increaseIndention(2);

            $self = $this;

            $task->setOutput(function($output) use ($io, $self) {
                $io->overwrite(sprintf('%s', $output), false);

                $self->needsNewline = true;
            });

            try {
                $task->execute($target, $version);

                $this->io->decreaseIndention(2);

                return true;
            } catch (\Exception $e) {
                $this->io->decreaseIndention(2);

                $this->io->renderException($e);

                $result = $this->tryAgain();

                if (false === $result) {
                    return false;
                }
            }
        }
    }

    protected function tryAgain()
    {
        while (true) {
            $answer = $this->io->select(
                '<info>Select an action</info>',
                array(
                    'a' => 'abort',
                    'r' => 'retry this task',
                    's' => 'skip this task and continue with the next',
                ),
                'r'
            );

            switch($answer) {
                case "a":
                    $this->io->write('Aborted.');
                    die();
                    break;
                case "r":
                    return true;
                    break;
                case "s":
                    return false;
                    break;
            }
        }
    }
}
