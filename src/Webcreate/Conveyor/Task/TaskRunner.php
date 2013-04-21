<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Task;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Webcreate\Conveyor\DependencyInjection\TransporterAwareInterface;
use Webcreate\Conveyor\Event\TaskRunnerEvents;
use Webcreate\Conveyor\IO\IOInterface;

class TaskRunner implements TransporterAwareInterface
{
    protected $tasks = array();
    protected $needsNewline = false;
    protected $transporter;
    protected $dispatcher;

    public function __construct(IOInterface $io, EventDispatcherInterface $dispatcher = null)
    {
        $this->io = $io;
        $this->dispatcher = $dispatcher;
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

    public function setTasks($tasks)
    {
        $this->tasks = $tasks;

        return $this;
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

        // also apply it to the tasks
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
        $total = count($this->tasks);

        foreach ($this->tasks as $i => $task) {
            $result = $this->executeTaskWithErrorHandling($task, $i, $total, $target, $version);

            $this->dispatch(TaskRunnerEvents::TASKRUNNER_POST_EXECUTE_TASK,
                new GenericEvent($task, array('index' => $i, 'total' => $total, 'result' => $result))
            );
        }

//        if (true === $this->needsNewline) {
//            $this->io->write('');
//            $this->needsNewline = false;
//        }
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

    protected function executeTaskWithErrorHandling(Task $task, $i, $total, $target, $version)
    {
        //$io = $this->io;

        if ($i > 0) {
//            if (true === $this->needsNewline) {
//                $this->io->write('');
//                $this->needsNewline = false;
//            }
//            $this->io->write('');
        }

        while (true) {
            $this->dispatch(TaskRunnerEvents::TASKRUNNER_PRE_EXECUTE_TASK,
                new GenericEvent($task, array('index' => $i, 'total' => $total))
            );

//            $this->io->write(sprintf('- Executing task <info>%s</info>', get_class($task)));
//            $this->io->increaseIndention(2);

//            $self = $this;
//
//            $task->setOutput(function($output) use ($io, $self) {
//                $io->overwrite(sprintf('%s', $output), false);
//
//                $self->needsNewline = true;
//            });

            try {
                $result = $task->execute($target, $version);

                //$this->io->decreaseIndention(2);

                return $result;
            } catch (\Exception $e) {
                //$this->io->decreaseIndention(2);

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
                    $this->io->setIndention(0);
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

        return true;
    }

    /**
     * Dispatch event when a dispatcher is available
     *
     * @param string $eventName
     * @param Event  $event
     */
    protected function dispatch($eventName, Event $event = null)
    {
        if (null !== $this->dispatcher) {
            $this->dispatcher->dispatch($eventName, $event);
        }
    }

    public function getDispatcher()
    {
        return $this->dispatcher;
    }
}
