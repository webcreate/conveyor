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
     * @param  string $logDir
     * @return Logger
     */
    public static function get($logDir)
    {
        if (0 === strpos($logDir, '~')) {
            $logDir = $_SERVER['HOME'] . substr($logDir, 1);
        }

        if (false === file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler($logDir . '/app.log', Logger::DEBUG));

        return $logger;
    }
}
