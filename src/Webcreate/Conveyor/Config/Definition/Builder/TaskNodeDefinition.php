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

use Symfony\Component\Config\Definition\Builder\NodeParentInterface;
use Symfony\Component\Config\Definition\Builder\ParentNodeDefinitionInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

use Webcreate\Conveyor\Config\Definition\TaskNode;
use Webcreate\Conveyor\Factory\TaskFactory;

/**
 * This class provides a fluent interface for defining an task node.
 *
 * @author Jeroen Fiege
 */
class TaskNodeDefinition extends NodeDefinition implements
        ParentNodeDefinitionInterface
{
    protected $nodeBuilder;
    protected $children;
    protected $taskFactory;

    public function __construct($name, NodeParentInterface $parent = null)
    {
        parent::__construct($name, $parent);

        $this->children = array(
                'type' => $this->getNodeBuilder()->node('type', 'scalar')
                        ->setParent($this)->isRequired(),);
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
        $node = new TaskNode($this->name, $this->parent);
        $node->setTaskFactory($this->taskFactory);

        foreach ($this->children as $child) {
            $child->parent = $node;
            $node->addChild($child->getNode());
        }

        if (null !== $this->normalization) {
            $node->setNormalizationClosures($this->normalization->before);
            $node->setXmlRemappings($this->normalization->remappings);
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
     * Set TaskFactory
     *
     * @param  TaskFactory|null                                                 $factory
     * @return \Webcreate\Conveyor\Config\Definition\Builder\TaskNodeDefinition
     */
    public function setTaskFactory($factory)
    {
        $this->taskFactory = $factory;

        return $this;
    }
}
