<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

foreach (array(__DIR__.'/../vendor/autoload.php', __DIR__.'/../../../autoload.php') as $autoloadLocation) {
    if (file_exists($autoloadLocation)) {
        return include $autoloadLocation;
    }
}

echo 'You must set up the project dependencies, run the following commands:'.PHP_EOL.
    'curl -sS https://getcomposer.org/installer | php'.PHP_EOL.
    'php composer.phar install'.PHP_EOL;
exit(1);
