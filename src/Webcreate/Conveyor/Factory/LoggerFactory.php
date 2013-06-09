<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Factory;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LoggerFactory
{
    /**
     * @param  string $logdir
     * @return Logger
     */
    public static function get($logdir)
    {
        if (0 === strpos($logdir, '~')) {
            $logdir = $_SERVER['HOME'] . substr($logdir, 1);
        }

        if (false === file_exists($logdir)) {
            mkdir($logdir, 0777, true);
        }

        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler($logdir . '/app.log', Logger::DEBUG));

        return $logger;
    }
}
