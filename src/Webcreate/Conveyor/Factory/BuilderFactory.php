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
use Webcreate\Conveyor\Builder\Builder;
use Webcreate\Conveyor\Config\YamlConfig;
use Webcreate\Conveyor\IO\IOInterface;

class BuilderFactory
{
    /**
     * @param  YamlConfig               $config
     * @param  IOInterface              $io
     * @param  EventDispatcherInterface $dispatcher
     * @param  TaskFactory              $taskFactory
     * @return Builder
     */
    public static function get(YamlConfig $config, IOInterface $io, EventDispatcherInterface $dispatcher, TaskFactory $taskFactory)
    {
        $_config = $config->getConfig();

        $buildDir = $_config['build']['dir'];

        $builderTasks = array(
            $taskFactory->get('export'),
        );

        foreach ($_config['build']['tasks'] as $taskConfig) {
            $task = $taskFactory->get($taskConfig['type'], $taskConfig);
            $builderTasks[] = $task;
        }

        $builder = new Builder($buildDir, $builderTasks, $io, $dispatcher);

        return $builder;
    }
}
