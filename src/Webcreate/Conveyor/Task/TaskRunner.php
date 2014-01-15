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

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Webcreate\Conveyor\DependencyInjection\TransporterAwareInterface;
use Webcreate\Conveyor\Event\TaskRunnerEvents;
use Webcreate\Conveyor\IO\IOInterface;

/**
 * @todo remove Transporter dependency because it is not related to running tasks!
 */
class TaskRunner implements TransporterAwareInterface
{
    /**
     * @var Task[]
     */
    protected $tasks = array();
    protected $needsNewline = false;
    protected $transporter;
    protected $dispatcher;

    /**
     * Constructor.
     *
     * @todo I think it's better NOT to have the taskrunner depend on the IOinterface
     *
     * @param IOInterface              $io
     * @param EventDispatcherInterface $dispatcher
     */
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

    /**
     * @param Task[] $tasks
     * @return $this
     */
    public function setTasks(array $tasks)
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
        // @todo remove this because it's plain wrong!
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
            while (true) {
                $this->dispatch(TaskRunnerEvents::TASKRUNNER_PRE_EXECUTE_TASK,
                    new GenericEvent($task, array('index' => $i, 'total' => $total))
                );

                $result = null;

                try {
                    $result = $task->execute($target, $version);

                    break;
                } catch (\Exception $e) {
                    $this->io->renderException($e);

                    // @todo instead of relying on the IOInterface here for asking an action,
                    //       better to trigger an event and have the listener ask an action.
                    if (false === $this->tryAgain()) {
                        break;
                    }
                }

                // @todo might be better to trigger a different event here, smt like TASKRUNNER_RETRY_EXECUTE_TASK
                $this->dispatch(TaskRunnerEvents::TASKRUNNER_POST_EXECUTE_TASK,
                    new GenericEvent($task, array('index' => $i, 'total' => $total, 'result' => $result))
                );
            }

            $this->dispatch(TaskRunnerEvents::TASKRUNNER_POST_EXECUTE_TASK,
                new GenericEvent($task, array('index' => $i, 'total' => $total, 'result' => $result))
            );
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

            switch ($answer) {
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
