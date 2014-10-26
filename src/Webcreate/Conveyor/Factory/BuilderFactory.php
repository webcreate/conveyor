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

class BuilderFactory
{
    public static function get(YamlConfig $config, $io, EventDispatcherInterface $dispatcher, $taskFactory)
    {
        $_config = $config->getConfig();

        $builddir = $_config['build']['dir'];

        $builderTasks = array(
            $taskFactory->get('export'),
        );

        foreach ($_config['build']['tasks'] as $taskConfig) {
            $task = $taskFactory->get($taskConfig['type'], $taskConfig);
            $builderTasks[] = $task;
        }

        $builder = new Builder($builddir, $builderTasks, $io, $dispatcher);

        return $builder;
    }
}
