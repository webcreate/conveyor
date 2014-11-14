<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Config;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;
use Webcreate\Conveyor\Config\Definition\DeployConfiguration;
use Webcreate\Conveyor\Factory\StrategyFactory;
use Webcreate\Conveyor\Factory\TaskFactory;
use Webcreate\Conveyor\Factory\TransporterFactory;
use Webcreate\Conveyor\Util\ArrayUtil;

/**
 * @todo refactor getConfig() method: might want to implement \ArrayAccess interface
 */
class YamlConfig
{
    protected $processedConfig;
    protected $loadedConfig;
    protected $compiledConfig;
    protected $parameters = array();
    protected $taskFactory;
    protected $transporterFactory;
    protected $strategyFactory;
    protected $file;

    /**
     * Constructor.
     *
     * @param  string                    $file
     * @param  TaskFactory               $taskFactory
     * @param  TransporterFactory        $transporterFactory
     * @param  StrategyFactory           $strategyFactory
     * @throws \InvalidArgumentException
     */
    public function __construct($file, TaskFactory $taskFactory, TransporterFactory $transporterFactory, StrategyFactory $strategyFactory)
    {
        if (!file_exists($file)) {
            throw new \InvalidArgumentException(sprintf('File %s does not exists', $file));
        }

        $this->file               = $file;
        $this->taskFactory        = $taskFactory;
        $this->transporterFactory = $transporterFactory;
        $this->strategyFactory    = $strategyFactory;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        $this->compile();

        return $this->compiledConfig;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return array
     */
    protected function process()
    {
        if (null === $this->processedConfig) {
            $this->load();

            $configuration = new DeployConfiguration($this->taskFactory, $this->transporterFactory, $this->strategyFactory);
            $processor = new Processor();

            $this->processedConfig = $processor->processConfiguration($configuration, $this->loadedConfig);
        }

        return $this->processedConfig;
    }

    /**
     * @return array
     */
    protected function load()
    {
        if (null === $this->loadedConfig) {
            $this->loadedConfig = Yaml::parse($this->file);
        }

        return $this->loadedConfig;
    }

    /**
     * @return mixed
     */
    protected function compile()
    {
        if (null === $this->compiledConfig) {
            $this->process();

            $this->compiledConfig = $this->replaceParameters($this->processedConfig, $this->parameters);
            $this->compiledConfig = $this->expandTasksTargetGroups($this->compiledConfig);
        }

        return $this->compiledConfig;
    }

    /**
     * @param  array $config
     * @param  array $parameters
     * @return array
     */
    protected function replaceParameters(array $config, array $parameters)
    {
        foreach ($config as &$value) {
            if (is_array($value)) {
                $value = $this->replaceParameters($value, $parameters);
            } elseif (is_string($value)) {
                foreach ($parameters as $key => $val) {
                    $value = str_replace(sprintf('{{%s}}', $key), $val, $value);
                }
            }
        }

        return $config;
    }

    /**
     * Expand target groups
     *
     * @param array $config
     * @return array
     */
    protected function expandTasksTargetGroups(array $config)
    {
        $sections = array(
            &$config['build']['tasks'],
            &$config['deploy']['before'],
            &$config['deploy']['after'],
            &$config['deploy']['final'],
        );

        foreach ($sections as &$section) {
            $section = $this->expandTasksTargetGroupsForSection($config, $section);
        }

        return $config;
    }

    /**
     * Expand target groups for a certain section
     *
     * @param array $config
     * @param array $section
     * @return array
     */
    protected function expandTasksTargetGroupsForSection(array $config, array $section = null)
    {
        if (null === $section) {
            return $section;
        }

        $groups = array();
        foreach ($config['targets'] as $targetName => $targetConfig) {
            foreach ($targetConfig['groups'] as $group) {
                $groups[$group][] = $targetName;
            }
        }

        foreach ($section as &$taskConfig) {
            $targets = $taskConfig['targets'];
            $expandedTargets = array();

            foreach ($targets as $target) {
                if (isset($groups[$target])) {
                    foreach ($groups[$target] as $groupTarget) {
                        $expandedTargets[] = $groupTarget;
                    }
                } else {
                    $expandedTargets[] = $target;
                }
            }

            $taskConfig['targets'] = $expandedTargets;
        }

        return $section;
    }

    /**
     * @return array
     */
    public function flatten()
    {
        $util = new ArrayUtil();

        return $util->flatten($this->getConfig());
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function setParameter($name, $value)
    {
        $this->process();

        $this->parameters[$name] = $value;

        if ('target' === $name) {
            foreach ($this->processedConfig['targets'][$value]['parameters'] as $key => $value) {
                $this->setParameter('target.' . $key, $value);
            }
        }

        $this->compiledConfig = null;
    }
}
