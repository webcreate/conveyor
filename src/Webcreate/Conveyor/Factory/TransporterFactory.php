<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Factory;

use Webcreate\Conveyor\Transporter\AbstractTransporter;

use Symfony\Component\DependencyInjection\ContainerInterface;

class TransporterFactory
{
    protected $container;
    protected $transporter    = array();
    protected $configurations = array();

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function addTransporter($serviceId, $alias, $configuration = false)
    {
        $this->transporters[$alias]   = $serviceId;
        $this->configurations[$alias] = $configuration;
    }

    public function getTransporters()
    {
        return $this->transporters;
    }

    /**
     * Returns transporter
     *
     * @param  string                    $alias   name of transporter
     * @param  array                     $options transporter settings
     * @throws \InvalidArgumentException
     * @return AbstractTransporter
     */
    public function get($alias, array $options = array())
    {
        $container = $this->container;

        if (!isset($this->transporters[$alias])) {
            throw new \InvalidArgumentException(sprintf('Transporter type \'%s\' does not exist', $alias));
        }

        $serviceId  = $this->transporters[$alias];
        $definition = $this->container->getDefinition($serviceId);

        $transporter = $this->container->get($serviceId);
        $transporter->setOptions($options);

        return $transporter;
    }

    /**
     * Returns transporter configuration
     *
     * @param  string                                                             $alias transporter name
     * @return Symfony\Component\Config\Definition\ConfigurationInterface|boolean
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
