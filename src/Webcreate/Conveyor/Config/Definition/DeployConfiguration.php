<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Config\Definition;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Webcreate\Conveyor\Factory\TaskFactory;
use Webcreate\Conveyor\Factory\TransporterFactory;

class DeployConfiguration implements ConfigurationInterface
{
    public function __construct(TaskFactory $taskFactory = null, TransporterFactory $transporterFactory = null)
    {
        $this->taskFactory = $taskFactory;
        $this->transporterFactory = $transporterFactory;
    }

    public function getConfigTreeBuilder()
    {
        $nodeBuilder = new NodeBuilder();
        $nodeBuilder->setNodeClass('task', __NAMESPACE__ . '\\Builder\\TaskNodeDefinition');
        $nodeBuilder->setNodeClass('transporter', __NAMESPACE__ . '\\Builder\\TransporterNodeDefinition');

        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('conveyor', 'array', $nodeBuilder);

        $validSchemes = array('git', 'svn');

        $rootNode
            ->children()
                ->arrayNode('repository')
                    ->isRequired()
                    ->children()
                        ->scalarNode('type')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('url')->isRequired()->cannotBeEmpty()->end()
                    ->end()
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function($v) {
                            $result = parse_url($v);

                            if (false === $result) {
                                throw new InvalidConfigurationException(sprintf(
                                    'Could not parse repository url "%s"',
                                    $v
                                ));
                            }

                            return array(
                                'type' => $result['scheme'],
                                'url'  => $v,
                            );
                        })
                    ->end()
                    ->validate()
                        ->ifTrue(function($v) use ($validSchemes) {
                            return false === in_array($v['type'], $validSchemes);
                        })
                        ->thenInvalid('Invalid repository type "%s". Valid types are: '.implode(', ', $validSchemes).'.')
                    ->end()
                ->end()
                ->arrayNode('targets')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->end()
                            ->scalarNode('url')->end()
                            ->node('transport', 'transporter')
                                ->setTransporterFactory($this->transporterFactory)
                                ->isRequired()
                            ->end()
                            ->arrayNode('parameters')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('build')
                    ->children()
                        ->scalarNode('dir')
                            ->defaultValue('/tmp/conveyor')
                            ->beforeNormalization()
                                ->ifString()
                                ->then(function($v) {
                                    // resolve home dir
                                    $path = preg_replace('/^\~/', getenv('HOME'), $v);

                                    return $path;
                                })
                            ->end()
                        ->end()
                        ->arrayNode('derived')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('source')->end()
                                    ->scalarNode('derived')->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('tasks')
                            ->prototype('task')
                                ->setTaskFactory($this->taskFactory)
                                ->children()
                                    ->arrayNode('targets')
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then(function($v) {
                                                return array($v);
                                            })
                                        ->end()
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('deploy')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('strategy')
                            ->addDefaultsIfNotSet()
                            ->beforeNormalization()
                                ->ifString()
                                ->then(function($v) {
                                    return array('type' => $v);
                                })
                            ->end()
                            ->children()
                                ->scalarNode('type')
                                    ->defaultValue('releases')
                                ->end()
                                // @todo the 'shared' section is only relevant for the ReleasesStrategy
                                //       refactor to a dedicated configuration per strategy type
                                ->arrayNode('shared')
                                    ->prototype('scalar')->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('before')
                            ->prototype('task')
                                ->setTaskFactory($this->taskFactory)
                                ->children()
                                    ->arrayNode('targets')
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then(function($v) {
                                                return array($v);
                                            })
                                        ->end()
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('after')
                            ->prototype('task')
                                ->setTaskFactory($this->taskFactory)
                                ->children()
                                    ->arrayNode('targets')
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then(function($v) {
                                                    return array($v);
                                                })
                                        ->end()
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('final')
                            ->prototype('task')
                                ->setTaskFactory($this->taskFactory)
                                ->children()
                                    ->arrayNode('targets')
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then(function($v) {
                                                    return array($v);
                                                })
                                        ->end()
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
