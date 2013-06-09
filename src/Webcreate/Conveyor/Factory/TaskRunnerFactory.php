<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Factory;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\Task\TaskRunner;

class TaskRunnerFactory
{
    /**
     * @param string      $taskConfigPath path to the tasks configuration in the conveyor.yml config
     * @param TaskFactory $taskFactory
     * @param $config
     * @param  IOInterface              $io
     * @param  EventDispatcherInterface $dispatcher
     * @return TaskRunner
     */
    public static function get($taskConfigPath, TaskFactory $taskFactory, $config, IOInterface $io, EventDispatcherInterface $dispatcher = null)
    {
        $_config = $config->getConfig();

        $taskRunner = new TaskRunner($io, $dispatcher);

        foreach ((array) self::getTasksConfig($_config, $taskConfigPath) as $t => $taskConfig) {
            $task = $taskFactory->get($taskConfig['type'], $taskConfig);
            $taskRunner->addTask($task);
        }

        return $taskRunner;
    }

    /**
     * @param  array  $config
     * @param  string $taskConfigPath
     * @return array
     */
    protected static function getTasksConfig(array $config, $taskConfigPath)
    {
        $path = explode('.', $taskConfigPath);

        $currentConfig = $config;

        foreach ($path as $p => $_path) {
            if (!isset($currentConfig[$_path])) {
//                 throw new \InvalidArgumentException(sprintf(
//                     'Invalid path "%s" at "%s"',
//                     $_path,
//                     implode('.', array_slice($path, 0, $p + 1))
//                 ));
                return array();
            }

            $currentConfig = $currentConfig[$_path];
        }

        return $currentConfig;
    }
}
