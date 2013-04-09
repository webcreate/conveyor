<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
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
