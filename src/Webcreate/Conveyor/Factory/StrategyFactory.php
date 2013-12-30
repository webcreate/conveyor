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

use Symfony\Component\DependencyInjection\ContainerInterface;
use Webcreate\Conveyor\Strategy\StrategyInterface;

class StrategyFactory
{
    protected $container;
    protected $strategies   = array();
    protected $configurations = array();

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function addStrategy($serviceId, $alias, $configuration = false)
    {
        $this->strategies[$alias]     = $serviceId;
        $this->configurations[$alias] = $configuration;
    }

    public function getStrategies()
    {
        return $this->strategies;
    }

    /**
     * Returns transporter
     *
     * @param  string                    $alias   name of transporter
     * @param  array                     $options transporter settings
     * @throws \InvalidArgumentException
     * @return StrategyInterface
     */
    public function get($alias, array $options = array())
    {
        if (!isset($this->strategies[$alias])) {
            throw new \InvalidArgumentException(sprintf('Strategy \'%s\' does not exist', $alias));
        }

        $serviceId  = $this->strategies[$alias];

        $transporter = $this->container->get($serviceId);
        $transporter->setOptions($options);

        return $transporter;
    }

    /**
     * Returns transporter configuration
     *
     * @param  string                                                             $alias transporter name
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
