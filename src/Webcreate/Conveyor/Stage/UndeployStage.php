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
use Webcreate\Conveyor\Task\SshTask;
use Webcreate\Conveyor\Task\TaskRunner;
use Webcreate\Conveyor\Transporter\AbstractTransporter;
use Webcreate\Conveyor\Util\FilePath;

class UndeployStage extends AbstractStage
{
    /**
     * @var AbstractTransporter
     */
    protected $transporter;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var TaskRunner
     */
    protected $taskRunner;

    /**
     * Performs an undeploy
     *  - run tasks before complete removal of base directory from remote (can be used to destroy databases, etc)
     *  - remove recursively basedir
     *
     * @param TaskRunner          $taskRunner
     * @param AbstractTransporter $transporter
     * @param IOInterface         $io
     */
    public function __construct(TaskRunner $taskRunner, AbstractTransporter $transporter, IOInterface $io)
    {
        $this->taskRunner  = $taskRunner;
        $this->transporter = $transporter;
        $this->io          = $io;
    }

    /**
     * @inheritdoc
     */
    public function supports(Context $context)
    {
        // check if directory exists
        return $this->transporter->exists($this->transporter->getPath());
    }

    /**
     * @inheritdoc
     */
    public function execute(Context $context)
    {
        // check if we have current release so we can run the tasks
        if ($this->currentReleaseExists($context)) {
            $this->executeTasks($context);
        }

        // finally remove the whole release folder
        $this->transporter->remove($this->transporter->getPath());
    }

    /**
     * Check in the remote if a current release exists
     *
     * @param Context $context
     *
     * @return bool
     */
    protected function currentReleaseExists(Context $context)
    {
        return $this->transporter->exists(
            FilePath::join($this->transporter->getPath(), $context->getStrategy()->getCurrentReleasePath())
        );
    }

    /**
     * Execute tasks (if any) before undeployment is completed
     *
     * @param Context $context
     *
     * @return void
     */
    protected function executeTasks(Context $context)
    {
        $target = $context->getTarget();
        $version = $context->getVersion();
        $strategy = $context->getStrategy();

        $tasks = $this->getSupportedTasks($target, $version);
        $this->taskRunner->setTasks($tasks);

        if (true === $this->taskRunner->hasTasks()) {
            foreach ($this->taskRunner->getTasks() as $task) {
                if ($task instanceof SshTask) {
                    $task->setOption('path', $strategy->getCurrentReleasePath());
                }
            }
            $this->executeTaskrunner($context);
        }
    }

    /**
     * Execute a single task
     *
     * @param Context $context
     *
     * @return void
     */
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
