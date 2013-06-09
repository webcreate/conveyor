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
    protected $sftp;

    public function connect($host)
    {
        $this->sftp = new \Net_SFTP($host);

        if (false == $this->sftp) {
            throw new \RuntimeException(sprintf('Could not connect to host %s', $host));
        }

        return true;
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
