<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Transporter\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class DefaultTransporterConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('transporter');

        $rootNode
            ->isRequired()
            ->children()
                ->scalarNode('host')->isRequired()->end()
                ->scalarNode('path')->isRequired()->end()
                ->scalarNode('user')->defaultValue(get_current_user())->end()
                ->scalarNode('pass')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
