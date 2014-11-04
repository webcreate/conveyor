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
use Webcreate\Conveyor\Transporter\AbstractTransporter;

class TransporterFactory
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var string[]
     */
    protected $transporters = array();

    /**
     * @var string[]
     */
    protected $configurations = array();

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string      $serviceId
     * @param string      $alias
     * @param string|bool $configuration
     */
    public function addTransporter($serviceId, $alias, $configuration = false)
    {
        $this->transporters[$alias]   = $serviceId;
        $this->configurations[$alias] = $configuration;
    }

    /**
     * @return string[]
     */
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
        if (!isset($this->transporters[$alias])) {
            throw new \InvalidArgumentException(sprintf('Transporter type \'%s\' does not exist', $alias));
        }

        $serviceId = $this->transporters[$alias];

        $transporter = $this->container->get($serviceId);
        $transporter->setOptions($options);

        return $transporter;
    }

    /**
     * Returns transporter configuration
     *
     * @param  string                                                              $alias transporter name
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
