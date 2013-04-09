<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Config\Definition\Builder;

use Symfony\Component\Config\Definition\Builder\ParentNodeDefinitionInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

use Webcreate\Conveyor\Config\Definition\TransporterNode;
use Webcreate\Conveyor\Factory\TaskTransporterFactory;

/**
 * This class provides a fluent interface for defining an transporter node.
 *
 * @author Jeroen Fiege
 */
class TransporterNodeDefinition extends NodeDefinition implements
        ParentNodeDefinitionInterface
{
    protected $nodeBuilder;
    protected $children;

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
        $node = new TransporterNode($this->name, $this->parent);
        $node->setTransporterFactory($this->transporterFactory);

        foreach ($this->children as $child) {
            $child->parent = $node;
            $node->addChild($child->getNode());
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
     * Set TransporterFactory
     *
     * @param  TransporterFactory|null                                                 $factory
     * @return \Webcreate\Conveyor\Config\Definition\Builder\TransporterNodeDefinition
     */
    public function setTransporterFactory($factory)
    {
        $this->transporterFactory = $factory;

        return $this;
    }
}
