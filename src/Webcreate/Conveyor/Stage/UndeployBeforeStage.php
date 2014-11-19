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
use Webcreate\Conveyor\Task\TaskRunner;
use Webcreate\Conveyor\Transporter\AbstractTransporter;
use Webcreate\Conveyor\Util\FilePath;

class UndeployBeforeStage extends AbstractStage
{
    /**
     * @var TaskRunner
     */
    protected $taskRunner;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var AbstractTransporter
     */
    protected $transporter;

    public function __construct(TaskRunner $taskRunner, IOInterface $io, AbstractTransporter $transporter)
    {
        $this->taskRunner = $taskRunner;
        $this->io = $io;
        $this->transporter = $transporter;
    }

    public function supports(Context $context)
    {
        // check if a former release (current) is available
        return $this->transporter->exists(
            FilePath::join($this->transporter->getPath(), $context->getStrategy()->getCurrentReleasePath())
        );
    }

    public function execute(Context $context)
    {
        $target = $context->getTarget();
        $version = $context->getVersion();
        $strategy = $context->getStrategy();

        $tasks = $this->getSupportedTasks($target, $version);
        $this->taskRunner->setTasks($tasks);

        if (true === $this->taskRunner->hasTasks()) {

            if (!$context->isForce()) {
                $answer = $this->io->askConfirmation(
                        'Are you sure to run the pre undeploy tasks? Data will be lost!' .
                        ' (y/N): ',
                    false
                );

                if (!$answer) {
                    return;
                }
            }

            foreach ($this->taskRunner->getTasks() as $task) {
                if ($task instanceof SshTask) {
                    $task->setOption('path', $strategy->getCurrentReleasePath());
                }
            }
            $this->executeTaskrunner($context);
        }
    }

    protected function executeTaskrunner(Context $context)
    {
        $this->io->write('');

        $this->io->increaseIndention(1);

        $this->taskRunner->execute($context->getTarget(), $context->getVersion());

        $this->io->decreaseIndention(1);
    }

    /**
     * Filters the tasks for given target
     *
     * @param  string                                 $target
     * @param  \Webcreate\Conveyor\Repository\Version $version
     * @return \Webcreate\Conveyor\Task\Task[]        task for the specific target
     */
    protected function getSupportedTasks($target, Version $version)
    {
        $tasks = array_filter($this->taskRunner->getTasks(),
            function ($task) use ($target, $version) {
                return (true === $task->supports($target, $version));
            }
        );

        // reindex
        $tasks = array_values($tasks);

        return $tasks;
    }
}
