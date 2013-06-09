<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Transporter;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Finder\Finder;

use Webcreate\Conveyor\Event\TransporterEvent;
use Webcreate\Conveyor\Event\TransporterEvents;

class FtpTransporter extends AbstractTransporter
{
    protected $stream;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->setDispatcher($dispatcher);
    }

    public function connect()
    {
        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_CONNECT, new TransporterEvent($this));

        $this->stream = ftp_connect($this->host);
        if (false == $this->stream) {
            throw new \RuntimeException(sprintf('Could not connect to host %s', $this->host));
        }
        ftp_pasv($this->stream, true);
    }

    public function login()
    {
        $success = ftp_login($this->stream, $this->username, $this->password);
    }

    public function connectAndLogin()
    {
        $this->connect();
        $this->login();
    }

    public function exists($path)
    {
        if (!$this->stream) {
            $this->connectAndLogin();
        }

        $pwd = ftp_pwd($this->stream);

        // try to change directory to see if it is an existing directory
        $result = @ftp_chdir($this->stream, $path);
        if (true === $result) {
            ftp_chdir($this->stream, $pwd); // change back to the original directory

            return true;
        } else {
            // list the parent directory and check if the file exists
            $parent = dirname($path);
            $options = '-a'; // list hidden
            $result = ftp_rawlist($this->stream, $options . ' ' . $parent);

            if (false !== $result) {
                foreach ($result as $line) {
                    if (false !== $pos = strrpos($line, basename($path))) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function mkdir($dest)
    {
        $pwd = dirname($dest);
        if (false === $this->exists($pwd)) {
            $this->mkdir($pwd);
        }

        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_MKDIR, new TransporterEvent($this, $dest));

        ftp_mkdir($this->stream, $dest);
    }

    public function get($src, $dest = null)
    {
        if (!$this->stream) {
            $this->connectAndLogin();
        }

        $realDest = $dest;

        if (null == $dest) {
            $realDest = tempnam(sys_get_temp_dir(), basename($src));
        }

        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_GET, new TransporterEvent($this, array('src' => $src, 'dest' => $dest)));

        $succes = ftp_get($this->stream, $realDest, $src, FTP_BINARY);

        if (null == $dest) {
            $content = file_get_contents($realDest);
            unlink($realDest);

            return $content;
        }
    }

    public function put($src, $dest)
    {
        if (!$this->stream) {
            $this->connectAndLogin();
        }

        if (false === file_exists($src)) {
            throw new \InvalidArgumentException(sprintf('Resource \'%s\' does not exist', $src));
        }

        if (is_file($src)) {
            $pwd = dirname($dest);
            if (false === $this->exists($pwd)) {
                $this->mkdir($pwd);
            }

            $file = new \SplFileInfo($src);

            $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_PUT, new TransporterEvent($this, array('dest' => $dest, 'src' => $file->getPathname())));

            ftp_put($this->stream, $dest, $file->getPathname(), FTP_BINARY);
        } else {
            if (!$this->exists($dest)) {
                $this->mkdir($dest);
            }

            $finder = new Finder();
            $test = $finder->in($src)->depth('== 0');

            foreach ($test as $file) {
                $this->put(rtrim($src, '/') . '/' . $file->getFilename(), rtrim($dest, '/')  . '/' . $file->getFilename());
            }
        }
    }

    public function putContent($content, $dest)
    {
        if (!$this->stream) {
            $this->connectAndLogin();
        }

        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_PUT_CONTENT, new TransporterEvent($this, array('dest' => $dest, 'content' => $content)));

        $tempfile = tempnam(sys_get_temp_dir(), 'conveyor');

        file_put_contents($tempfile, $content);

        ftp_put($this->stream, $dest, $tempfile, FTP_BINARY);

        unlink($tempfile);
    }

    /**
     * Creates a symlink on the remote server
     *
     * @param $src
     * @param $dest
     * @throws \RuntimeException
     * @return mixed
     */
    public function symlink($src, $dest)
    {
        throw new \RuntimeException('Symlinking not supported');
    }

    /**
     * Copies a file/directory on the remote host
     *
     * @param  string $src
     * @param  string $dest
     * @param  bool   $recursive
     * @return mixed
     */
    public function copy($src, $dest, $recursive = true)
    {
        throw new \RuntimeException('Copy (yet) not supported');
    }

    /**
     * Removes a file/directory on the remote host
     *
     * @param  string $path
     * @param  bool   $recursive
     * @return mixed
     */
    public function remove($path, $recursive = true)
    {
        throw new \RuntimeException('Remove (yet) not supported');
    }

    public function __destruct()
    {
        if ($this->stream) {
            ftp_close($this->stream);
        }
    }
}
