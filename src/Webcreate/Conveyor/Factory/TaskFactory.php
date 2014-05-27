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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Webcreate\Conveyor\Task\Task;

class TaskFactory
{
    protected $container;

    protected $tasks          = array();
    protected $configurations = array();

    public function __construct(ContainerBuilder $container)
    {
        $this->container = $container;
    }

    public function addTask($serviceId, $alias, $configuration = false)
    {
        $this->tasks[$alias]          = $serviceId;
        $this->configurations[$alias] = $configuration;
    }

    public function getTasks()
    {
        return $this->tasks;
    }

    /**
     * Returns task
     *
     * @param  string $alias   name of the task
     * @param  array  $options configuration options for the task
     * @return Task
     */
    public function get($alias, array $options = array())
    {
        $container = $this->container;

        $serviceId  = $this->tasks[$alias];
        $definition = $this->container->getDefinition($serviceId);

        $task = $this->container->get($serviceId);
        $task->setOptions($options);

        return $task;
    }

    /**
     * Returns task configuration
     *
     * @param  string                                                              $alias task name
     * @return \Symfony\Component\Config\Definition\ConfigurationInterface|boolean
     */
    public function configuration($alias)
    {
        $configurationClass = $this->configurations[$alias];

        if (false !== $configurationClass) {
            return new $configurationClass();
        }

        return false;
    }
}
