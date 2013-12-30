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

use Webcreate\Conveyor\Factory\StrategyFactory;
use Webcreate\Conveyor\Factory\TaskFactory;
use Webcreate\Conveyor\Factory\TransporterFactory;
use Webcreate\Conveyor\Util\ArrayUtil;
use Webcreate\Conveyor\Config\Definition\DeployConfiguration;

/**
 * @todo refactor getConfig() method: might want to implement \ArrayAccess interface
 *
 */
class YamlConfig
{
    protected $processed;
    protected $parameters = array();
    protected $taskFactory;
    protected $transporterFactory;
    protected $strategyFactory;
    protected $file;

    public function __construct($file, TaskFactory $taskFactory, TransporterFactory $transporterFactory, StrategyFactory $strategyFactory)
    {
        if (!file_exists($file)) {
            throw new \InvalidArgumentException(sprintf('File %s does not exists', $file));
        }

        $this->file        = $file;
        $this->taskFactory = $taskFactory;
        $this->transporterFactory = $transporterFactory;
        $this->strategyFactory = $strategyFactory;
    }

    public function getConfig()
    {
        if (null === $this->processed) {
            $this->process();
        }

        return $this->processed;
    }

    public function getFile()
    {
        return $this->file;
    }

    protected function process()
    {
        $config = Yaml::parse($this->file);

        $config = $this->replaceParameters($config, $this->parameters);

        $configuration = new DeployConfiguration($this->taskFactory, $this->transporterFactory, $this->strategyFactory);
        $processor = new Processor();

        return $this->processed = $processor->processConfiguration($configuration, $config);
    }

    protected function replaceParameters($config, $parameters)
    {
        foreach($config as &$value) {
            if (is_array($value)) {
                $value = $this->replaceParameters($value, $parameters);
            } elseif (is_string($value)) {
                foreach($parameters as $key => $val) {
                    $value = str_replace(sprintf('{{%s}}', $key), $val, $value);
                }
            }
        }

        return $config;
    }

    public function flatten()
    {
        $util = new ArrayUtil();

        return $util->flatten($this->getConfig());
    }

    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;

        if ('target' === $name) {
            foreach($this->processed['targets'][$value]['parameters'] as $key => $value) {
                $this->setParameter('target.' . $key, $value);
            }
        }

        $this->processed = null;
    }
}
