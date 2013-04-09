<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
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
