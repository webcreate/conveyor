<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Builder;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Webcreate\Conveyor\Event\TaskRunnerEvents;
use Webcreate\Conveyor\Task\Result\ExecuteResult;
use Webcreate\Conveyor\Context;
use Webcreate\Conveyor\Task\Task;
use Webcreate\Conveyor\Event\BuilderEvents;
use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\Repository\Version;
use Webcreate\Conveyor\Task\TaskRunner;

/**
 * @todo remove dependency on Context, have the BuildStage request the files
 */
class Builder
{
    protected $builddir;
    protected $io;
    protected $dispatcher;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var \Webcreate\Conveyor\Task\TaskRunner
     */
    protected $taskRunner;

    /**
     * Constructor.
     *
     * @todo Can we refactor so we don't have to depend on the IOInterface?
     *
     * @param string                   $builddir   destionation path for build
     * @param array                    $tasks
     * @param IOInterface              $io
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct($builddir, array $tasks = array(),
            IOInterface $io = null, EventDispatcherInterface $dispatcher = null
    )
    {
        $this->builddir   = rtrim($builddir, '/');
        $this->io         = $io;
        $this->dispatcher = $dispatcher;
        $this->taskRunner = $this->createTaskRunner($tasks);
    }

    /**
     * Creates a task runner for builder tasks
     *
     * @param Task[] $tasks
     * @return TaskRunner
     */
    protected function createTaskRunner(array $tasks)
    {
        $self = $this;

        $taskRunner = new TaskRunner($this->io, new EventDispatcher());
        $taskRunner->setTasks($tasks);

        $taskRunner->getDispatcher()->addListener(
            TaskRunnerEvents::TASKRUNNER_PRE_EXECUTE_TASK,
            function (GenericEvent $event) use ($self) {
                $task  = $event->getSubject();
                $t     = $event->getArgument('index');
                $total = $event->getArgument('total');

                $self->dispatch(BuilderEvents::BUILDER_PRE_TASK,
                    new GenericEvent($task, array('index' => $t, 'total' => $total))
                );
            }
        );

        $taskRunner->getDispatcher()->addListener(
            TaskRunnerEvents::TASKRUNNER_POST_EXECUTE_TASK,
            function (GenericEvent $event) use ($self) {
                $task   = $event->getSubject();
                $t      = $event->getArgument('index');
                $total  = $event->getArgument('total');
                $result = $event->getArgument('result');

                if ($result instanceof ExecuteResult) {
                    $self->applyResultToFilelist($result);
                }

                $self->dispatch(BuilderEvents::BUILDER_POST_TASK,
                    new GenericEvent($task, array('index' => $t, 'total' => $total))
                );
            }
        );

        return $taskRunner;
    }

    /**
     * Add task to the build process
     *
     * @param Task $task
     * @return $this
     */
    public function addTask(Task $task)
    {
        $this->taskRunner->addTask($task);

        return $this;
    }

    /**
     * Return destination path for build
     *
     * @return string
     */
    public function getBuildDir()
    {
        return $this->builddir;
    }

    /**
     * @deprecated I rather not have the builder depend on the context,
     *             that's something for the BuildStage
     *
     * @param Context $context
     * @return $this
     */
    public function setContext(Context $context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Run added tasks
     *
     * @param string  $target
     * @param Version $version
     */
    public function build($target, Version $version)
    {
        $this->dispatch(BuilderEvents::BUILDER_PRE_BUILD);

        $tasks = $this->getSupportedTasks($target, $version);

        $this->taskRunner->setTasks($tasks);

        $this->taskRunner->execute($target, $version);

        $this->dispatch(BuilderEvents::BUILDER_POST_BUILD);
    }

    protected function applyResultToFilelist(ExecuteResult $result)
    {
        $filesModified = $this->context->getFilesModified();

        foreach ($result->getDerived() as $pattern) {
            $filesModified->add($pattern);
        }

        foreach ($result->getRemoved() as $pattern) {
            $filesModified->remove($pattern);
        }
    }

    /**
     * Filters the tasks for given target
     *
     * @param  string                                 $target
     * @param  \Webcreate\Conveyor\Repository\Version $version
     * @return Task[]                                 task for the specific target
     */
    protected function getSupportedTasks($target, Version $version)
    {
        $tasks = array_filter($this->taskRunner->getTasks(), function($task) use ($target, $version) {
            return (true === $task->supports($target, $version));
        });

        // reindex
        $tasks = array_values($tasks);

        return $tasks;
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
}
