<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;
use Webcreate\Conveyor\Config\Definition\DeployConfiguration;

class DeployConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testRepositoryAsString()
    {
        $yaml = <<<EOF
conveyor:
    repository: svn://subversion.dev/repository
EOF;

        $config = Yaml::parse($yaml);

        $configuration = new DeployConfiguration();
        $processor = new Processor();

        $result = $processor->processConfiguration($configuration, $config);

        $this->assertEquals('svn', $result['repository']['type']);
    }
}
