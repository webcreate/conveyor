<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
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
