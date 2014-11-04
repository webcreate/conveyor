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

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Webcreate\Conveyor\Task\Task;

class TaskFactory
{
    /**
     * @var ContainerBuilder
     */
    protected $container;

    /**
     * @var string[]
     */
    protected $tasks = array();

    /**
     * @var string[]
     */
    protected $configurations = array();

    /**
     * @param ContainerBuilder $container
     */
    public function __construct(ContainerBuilder $container)
    {
        $this->container = $container;
    }

    /**
     * @param string      $serviceId
     * @param string      $alias
     * @param string|bool $configuration
     */
    public function addTask($serviceId, $alias, $configuration = false)
    {
        $this->tasks[$alias]          = $serviceId;
        $this->configurations[$alias] = $configuration;
    }

    /**
     * @return array
     */
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
        $serviceId  = $this->tasks[$alias];

        $task = $this->container->get($serviceId);
        $task->setOptions($options);

        return $task;
    }

    /**
     * Returns task configuration
     *
     * @param  string                         $alias task name
     * @return ConfigurationInterface|boolean
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
