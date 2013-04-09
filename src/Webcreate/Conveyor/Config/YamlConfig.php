<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Config;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

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

    public function __construct($file, TaskFactory $taskFactory, TransporterFactory $transporterFactory)
    {
        if (!file_exists($file)) {
            throw new \InvalidArgumentException(sprintf('File %s does not exists', $file));
        }

        $this->file        = $file;
        $this->taskFactory = $taskFactory;
        $this->transporterFactory = $transporterFactory;
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

        $configuration = new DeployConfiguration($this->taskFactory, $this->transporterFactory);
        $processor = new Processor();

        return $this->processed = $processor->processConfiguration($configuration, $config);
    }

    public function flatten()
    {
        $util = new ArrayUtil();

        return $util->flatten($this->getConfig());
    }
}
