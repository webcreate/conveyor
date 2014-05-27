<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Stage;

use Webcreate\Conveyor\Context;
use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\Repository\Version;
use Webcreate\Conveyor\Stage\AbstractStage;
use Webcreate\Conveyor\Task\TaskRunner;

class DeployBeforeStage extends AbstractStage
{
    protected $taskRunner;
    protected $io;

    public function __construct(TaskRunner $taskRunner, IOInterface $io)
    {
        $this->taskRunner = $taskRunner;
        $this->io = $io;
    }

    public function supports(Context $context)
    {
        return true;
    }

    public function execute(Context $context)
    {
        $target = $context->getTarget();
        $version = $context->getVersion();

        $tasks = $this->getSupportedTasks($target, $version);
        $this->taskRunner->setTasks($tasks);

        if (true === $this->taskRunner->hasTasks()) {
            $this->executeTaskrunner($context);
        }
    }

    protected function executeTaskrunner(Context $context)
    {
        $this->io->write('');

        if (true === $context->isSimulate()) {
            $this->io->write(sprintf('Simulating tasks before deploy'));
        } else {
            $this->io->write(sprintf('Running tasks before deploy'));
        }

        $this->io->increaseIndention(1);

        if (true === $context->isSimulate()) {
            $this->taskRunner->simulate($context->getTarget(), $context->getVersion());
        } else {
            $this->taskRunner->execute($context->getTarget(), $context->getVersion());
        }

        $this->io->decreaseIndention(1);
    }

    /**
     * Filters the tasks for given target
     *
     * @param  string $target
     * @param \Webcreate\Conveyor\Repository\Version $version
     * @return \Webcreate\Conveyor\Task\Task[]                 task for the specific target
     */
    protected function getSupportedTasks($target, Version $version)
    {
        $tasks = array_filter($this->taskRunner->getTasks(),
            function($task) use ($target, $version) {
                return (true === $task->supports($target, $version));
            }
        );

        // reindex
        $tasks = array_values($tasks);

        return $tasks;
    }
}
