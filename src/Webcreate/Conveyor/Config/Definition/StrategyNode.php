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

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use Webcreate\Conveyor\Factory\StrategyFactory;

class StrategyNode extends ArrayNode
{
    protected $prepared = false;
    protected $originalChildren = array();

    /**
     * @var StrategyFactory
     */
    protected $strategyFactory;

    /**
     * Constructor.
     *
     * @param string        $name
     * @param NodeInterface $parent
     */
    public function __construct($name, NodeInterface $parent = null)
    {
        parent::__construct($name, $parent);
    }

    /**
     * Set transporter factory
     *
     * @param StrategyFactory|null $factory
     */
    public function setStrategyFactory($factory)
    {
        $this->strategyFactory = $factory;
    }

    /**
     * Takes child nodes from a ConfigurtionInterface instance
     * and adds these to this node
     *
     * @param  string                        $type
     * @throws InvalidConfigurationException
     */
    protected function prepareChildren($type)
    {
        if (null === $this->strategyFactory) {
            return;
        }

        // when we hit this function for the first time,
        // we store the original children. Each time we
        // hit this function we reset the children to the
        // original ones.
        if (false === $this->prepared) {
            $this->originalChildren = $this->children;
            $this->prepared = true;
        }

        $strategies = $this->strategyFactory->getStrategies();

        $this->children = $this->originalChildren;

        if (isset($strategies[$type])) {
            $configuration = $this->strategyFactory->configuration($type);

            if ($configuration instanceof ConfigurationInterface) {
                $tree = $configuration->getConfigTreeBuilder()->buildTree();
                foreach ($tree->getChildren() as $child) {
                    $this->addChild($child);
                }
            }
        } else {
            throw new InvalidConfigurationException(sprintf(
                    'Strategy type "%s" does not exist at path "%s". Did you mean any of %s?', $type,
                    $this->getPath(),
                    implode(', ', array_keys($strategies))
            ));
        }
    }

    /**
     * We hook into the validateType method, this
     * gets called form the normalize method.
     *
     * @param mixed $value
     *
     * @throws InvalidTypeException
     */
    protected function validateType($value)
    {
        if (isset($value['type'])) {
            $this->prepareChildren($value['type']);
        } else {
            // ignore extra keys so the error message will be
            // focused on the missing type field
            $this->setIgnoreExtraKeys(true);
        }

        parent::validateType($value);
    }

    public function getDefaultValue()
    {
        if ($this->children['type']->getDefaultValue()) {
            $this->prepareChildren($this->children['type']->getDefaultValue());
        }

        return parent::getDefaultValue();
    }
}
