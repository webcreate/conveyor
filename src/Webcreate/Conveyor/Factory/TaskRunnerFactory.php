<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Factory;

use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\Task\TaskRunner;

class TaskRunnerFactory
{
    /**
     * @param string      $taskConfigPath path to the tasks configuration in the conveyor.yml config
     * @param TaskFactory $taskFactory
     * @param $config
     * @param  IOInterface $io
     * @return TaskRunner
     */
    public static function get($taskConfigPath, TaskFactory $taskFactory, $config, IOInterface $io)
    {
        $_config = $config->getConfig();

        $taskRunner = new TaskRunner($io);

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