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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Pass to add tagged strategies to the StrategyFactory
 *
 */
class StrategyCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('strategy.factory')) {
            return;
        }

        $definition = $container->getDefinition('strategy.factory');
        $taggedServices = $container->findTaggedServiceIds('strategy');

        foreach ($taggedServices as $id => $tagsAttributes) {
            foreach ($tagsAttributes as $attributes) {
                $attributes += array(
                    'alias'         => $id,
                    'configuration' => false,
                );

                if ($attributes['configuration']) {
                    $ref = new \ReflectionClass($attributes['configuration']);
                    if (!$ref->implementsInterface('Symfony\Component\Config\Definition\ConfigurationInterface')) {
                        throw new \Exception(sprintf('The tag attribute "configuration" for service "%s" must implement "Symfony\Component\Config\Definition\ConfigurationInterface"', $id));
                    }
                }

                $definition->addMethodCall(
                    'addStrategy',
                    array($id, $attributes['alias'], $attributes['configuration'])
                );
            }
        }
    }
}
