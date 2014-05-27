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
use Webcreate\Conveyor\Event\TransporterEvent;
use Webcreate\Conveyor\Event\TransporterEvents;
use Webcreate\Util\Cli;

class ScpTransporter extends AbstractTransporter implements SshCapableTransporterInterface
{
    protected $cli;
    protected $_exists = array();

    public function __construct(EventDispatcherInterface $dispatcher, Cli $cli)
    {
        parent::__construct($dispatcher);

        $this->cli = $cli;
    }

    /**
     * Checks if a file or directory exists on the remote server
     *
     * @param  string $path remote source path
     * @return bool   true when the resource exists, false otherwise
     */
    public function exists($path)
    {
        if (false === isset($this->_exists[$path])) {
            $remoteCommand = sprintf("test -e %s", $path);
            $commandline   = sprintf("ssh %s@%s \"%s\"", $this->getUser(), $this->getHost(), $remoteCommand);

            $exitCode = $this->cli->execute($commandline);

            $this->_exists[$path] = (0 === $exitCode);
        }

        return $this->_exists[$path];
    }

    /**
     * Create a directory on the remote server
     *
     * @param  string            $dest remote path
     * @throws \RuntimeException
     */
    public function mkdir($dest)
    {
        $pwd = dirname($dest);
        if (false === $this->exists($pwd)) {
            $this->mkdir($pwd);
        }

        $remoteCommand = sprintf("mkdir '%s'", $dest);
        $commandline   = sprintf("ssh %s@%s \"%s\"", $this->getUser(), $this->getHost(), $remoteCommand);

        $this->_exists[$dest] = true;

        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_MKDIR, new TransporterEvent($this, $dest));

        if ($this->cli->execute($commandline)) {
            throw new \RuntimeException($this->cli->getErrorOutput());
        }
    }

    /**
     * Retrieve file or directory from remote server
     *
     * @param  string            $src  remote source path
     * @param  string            $dest (optional) local destination path
     * @throws \RuntimeException
     * @return string
     */
    public function get($src, $dest = null)
    {
        $realDest = $dest;

        if (null == $dest) {
            $realDest = tempnam(sys_get_temp_dir(), basename($src));
        }

        $commandline = sprintf("scp %s@%s:%s \"%s\"", $this->getUser(), $this->getHost(), $src, $realDest);

        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_GET, new TransporterEvent($this, array('src' => $src, 'dest' => $dest)));

        if ($this->cli->execute($commandline)) {
            throw new \RuntimeException($this->cli->getErrorOutput());
        }

        if (null == $dest) {
            $content = file_get_contents($realDest);
            unlink($realDest);

            return $content;
        }
    }

    /**
     * Upload a file or directory to remote server
     *
     * @param  string                    $src  local source path
     * @param  string                    $dest remote destination path
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function put($src, $dest)
    {
        if (false === file_exists($src)) {
            throw new \InvalidArgumentException(sprintf('Resource \'%s\' does not exist', $src));
        }

        $pwd = dirname($dest);
        if (false === $this->exists($pwd)) {
            $this->mkdir($pwd);
        }

        $commandline = sprintf("scp \"%s\" '%s@%s:%s'", $src, $this->getUser(), $this->getHost(), $dest);

        $this->_exists[$dest] = true;

        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_PUT, new TransporterEvent($this, array('dest' => $dest, 'src' => $src)));

        if ($this->cli->execute($commandline)) {
            throw new \RuntimeException($this->cli->getErrorOutput());
        }
    }

    /**
     * Upload a string to remote server
     *
     * @param  string            $content content
     * @param  string            $dest    remote destination path
     * @throws \RuntimeException
     */
    public function putContent($content, $dest)
    {
        $temp_file = tempnam(sys_get_temp_dir(), 'rsync' . time());

        file_put_contents($temp_file, $content);

        $commandline = sprintf("scp \"%s\" '%s@%s:%s'", $temp_file, $this->getUser(), $this->getHost(), $dest);

        $this->_exists[$dest] = true;

        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_PUT_CONTENT, new TransporterEvent($this, array('dest' => $dest, 'src' => $temp_file, 'content' => $content)));

        if ($this->cli->execute($commandline)) {
            @unlink($temp_file);

            throw new \RuntimeException($this->cli->getErrorOutput());
        }

        @unlink($temp_file);
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
        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_SYMLINK, new TransporterEvent($this, array('dest' => $dest, 'src' => $src)));

        $remoteCommand = sprintf("ln -s %s %s", $src, $dest);
        $commandline   = sprintf("ssh %s@%s \"%s\"", $this->getUser(), $this->getHost(), $remoteCommand);

        if ($this->cli->execute($commandline)) {
            throw new \RuntimeException($this->cli->getErrorOutput());
        }
    }

    /**
     * Checks for symlink on the remote server
     *
     * @param $dest
     * @return bool
     */
    public function isSymlink($dest)
    {
        $remoteCommand = sprintf("readlink %s", $dest);
        $commandline   = sprintf("ssh %s@%s \"%s\"", $this->getUser(), $this->getHost(), $remoteCommand);

        if ($this->cli->execute($commandline)) {
            return false;
        }

        return ('' !== trim($this->cli->getOutput()));
    }

    /**
     * Copies a file/directory on the remote host
     *
     * @param  string            $src
     * @param  string            $dest
     * @param  bool              $recursive
     * @throws \RuntimeException
     * @return mixed
     */
    public function copy($src, $dest, $recursive = true)
    {
        $recursiveFlag = ($recursive ? 'r' : '');

        $remoteCommand = sprintf("cp -{$recursiveFlag}f %s %s", $src, $dest);
        $commandline   = sprintf("ssh %s@%s \"%s\"", $this->getUser(), $this->getHost(), $remoteCommand);

        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_COPY, new TransporterEvent($this, array('dest' => $dest, 'src' => $src)));

        if ($this->cli->execute($commandline)) {
            throw new \RuntimeException($this->cli->getErrorOutput());
        }
    }

    /**
     * Removes a file/directory on the remote host
     *
     * @param  string            $path
     * @param  bool              $recursive
     * @throws \RuntimeException
     * @return mixed
     */
    public function remove($path, $recursive = true)
    {
        $recursiveFlag = ($recursive ? 'r' : '');

        $remoteCommand = sprintf("rm -{$recursiveFlag}f %s", $path);
        $commandline   = sprintf("ssh %s@%s \"%s\"", $this->getUser(), $this->getHost(), $remoteCommand);

        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_REMOVE, new TransporterEvent($this, array('path' => $path)));

        if ($this->cli->execute($commandline)) {
            throw new \RuntimeException($this->cli->getErrorOutput());
        }
    }

    /**
     * @todo this is just a simple implementation which should be improved
     *
     * @param $command
     * @param  null              $callback
     * @throws \RuntimeException
     */
    public function exec($command, $callback = null)
    {
        $commandline = sprintf("ssh %s@%s \"%s\"", $this->getUser(), $this->getHost(), $command);

        if ($this->cli->execute($commandline, $callback)) {
            throw new \RuntimeException($this->cli->getErrorOutput());
        }
    }

    /**
     * Lists files and directories
     *
     * returns an array with the following format:
     *
     * array(
     *   'filename' => array(
     *     'type' => 'directory', // or 'file'
     *     'mtime' => new \DateTime(),
     *   ),
     * );
     *
     * @param  string            $path
     * @throws \RuntimeException
     * @return array
     */
    public function ls($path)
    {
        throw new \RuntimeException('Listing of files and directories is not supported (yet)');
    }
}
