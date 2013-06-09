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

use Webcreate\Conveyor\Builder\Builder;

class BuilderFactory
{
    public static function get($config, $io, $dispatcher, $taskFactory)
    {
        $_config = $config->getConfig();

        $builddir = $_config['build']['dir'];

        $builderTasks = array(
            $taskFactory->get('export'),
        );

        foreach ($_config['build']['tasks'] as $taskConfig) {
            $task = $taskFactory->get($taskConfig['type'], $taskConfig);
            $builderTasks = array_merge($builderTasks, array($task));
        }

        $builder = new Builder($builddir, $builderTasks, $io, $dispatcher);

        return $builder;
    }
}
