<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Config\Definition\Builder;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeParentInterface;
use Symfony\Component\Config\Definition\Builder\ParentNodeDefinitionInterface;
use Webcreate\Conveyor\Config\Definition\StrategyNode;
use Webcreate\Conveyor\Factory\StrategyFactory;

/**
 * This class provides a fluent interface for defining an transporter node.
 *
 * @author Jeroen Fiege
 */
class StrategyNodeDefinition extends ArrayNodeDefinition implements
        ParentNodeDefinitionInterface
{
    protected $nodeBuilder;
    protected $children;

    /**
     * @var StrategyFactory
     */
    protected $strategyFactory;

    public function __construct($name, NodeParentInterface $parent = null)
    {
        parent::__construct($name, $parent);
    }

    /**
     * Returns a node builder to be used to add children and prototype
     *
     * @return NodeBuilder The node builder
     */
    protected function getNodeBuilder()
    {
        if (null === $this->nodeBuilder) {
            $this->nodeBuilder = new NodeBuilder();
        }

        return $this->nodeBuilder->setParent($this);
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Config\Definition\Builder.NodeDefinition::createNode()
     */
    protected function createNode()
    {
        $node = new StrategyNode($this->name, $this->parent);
        $node->setStrategyFactory($this->strategyFactory);

        $node->setAddIfNotSet($this->addDefaults);
        $node->setRequired($this->required);

        foreach ($this->children as $child) {
            $child->parent = $node;
            $node->addChild($child->getNode());
        }

        if (null !== $this->normalization) {
            $node->setNormalizationClosures($this->normalization->before);
            $node->setXmlRemappings($this->normalization->remappings);
        }

        if (null !== $this->validation) {
            $node->setFinalValidationClosures($this->validation->rules);
        }

        return $node;
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Config\Definition\Builder.ParentNodeDefinitionInterface::children()
     */
    public function children()
    {
        return $this->getNodeBuilder();
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Config\Definition\Builder.ParentNodeDefinitionInterface::append()
     */
    public function append(NodeDefinition $node)
    {
        $this->children[$node->name] = $node->setParent($this);

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Config\Definition\Builder.ParentNodeDefinitionInterface::setBuilder()
     */
    public function setBuilder(NodeBuilder $builder)
    {
        $this->nodeBuilder = $builder;
    }

    /**
     * Set StrategyFactory
     *
     * @param  StrategyFactory|null                                                    $factory
     * @return StrategyNodeDefinition
     */
    public function setStrategyFactory($factory)
    {
        $this->strategyFactory = $factory;

        return $this;
    }
}
