<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Pass to add tagged transporters to the TransporterFactory
 *
 */
class TransporterCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('transporter.factory')) {
            return;
        }

        $definition = $container->getDefinition('transporter.factory');
        $taggedServices = $container->findTaggedServiceIds('transporter');

        foreach ($taggedServices as $id => $tagsAttributes) {
            foreach ($tagsAttributes as $attributes) {
                $taskDefinition = $container->getDefinition($id);

                $attributes += array(
                    'alias'         => $id,
                    'configuration' => false,
                );

                // validate configuration attribute
                if (false === $attributes['configuration']) {
                    $attributes['configuration'] = 'Webcreate\Conveyor\Transporter\Configuration\DefaultTransporterConfiguration';
                }

                $ref = new \ReflectionClass($attributes['configuration']);
                if (!$ref->implementsInterface('Symfony\Component\Config\Definition\ConfigurationInterface')) {
                    throw new \Exception(sprintf('The tag attribute "configuration" for service "%s" must implement "Symfony\Component\Config\Definition\ConfigurationInterface"', $id));
                }

                $definition->addMethodCall(
                    'addTransporter',
                    array($id, $attributes['alias'], $attributes['configuration'])
                );
            }
        }
    }
}
