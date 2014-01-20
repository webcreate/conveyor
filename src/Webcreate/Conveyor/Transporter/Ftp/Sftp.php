<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Transporter\Ftp;

class Sftp
{
    /**
     * @var \Net_SFTP
     */
    protected $sftp;

    /**
     * Possible Phpseclib error
     *
     * @var null|string
     */
    protected $error = null;

    public function connect($host, $port = 22)
    {
        $oldErrorHandler = set_error_handler(array(&$this, 'errorHandler'), E_USER_NOTICE);

        $this->error = null;
        $this->sftp = new \Net_SFTP($host, $port);

        set_error_handler($oldErrorHandler, E_USER_NOTICE);

        if (false == $this->sftp) {
            throw new \RuntimeException(sprintf('Could not connect to host %s', $host));
        }

        if (null !== $this->error) {
            throw new \RuntimeException(sprintf('Could not connect to host %s: %s', $host, $this->error));
        }

        return true;
    }

    protected function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $this->error = $errstr;
    }

    public function isConnected()
    {
        if (!$this->sftp || !is_resource($this->sftp->fsock) || feof($this->sftp->fsock)) {
            return false;
        }

        return true;
    }

    public function __call($name, $arguments)
    {
        if ($this->sftp) {
            $errorlevel = error_reporting(E_ALL ^ E_NOTICE);

            $retval = call_user_func_array(array(&$this->sftp, $name), $arguments);

            error_reporting($errorlevel);

            return $retval;
        }

        return false;
    }
}
