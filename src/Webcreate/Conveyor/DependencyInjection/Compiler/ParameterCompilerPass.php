<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

use Webcreate\Conveyor\Util\ArrayUtil;

/**
 * Pass to add the configurtion from conveyor.yml
 * to the container.
 *
 */
class ParameterCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $ymlconf = $container->get('config');
        $util = new ArrayUtil();

        $parameters = $util->flatten($ymlconf->getConfig());
        foreach ($parameters as $name => $value) {
            $container->setParameter($name, $value);
        }
    }
}
