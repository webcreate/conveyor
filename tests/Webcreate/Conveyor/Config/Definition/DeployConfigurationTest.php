<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
