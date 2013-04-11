<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Transporter;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class AbstractTransporter
{
    protected $username;
    protected $password;
    protected $path;
    protected $host;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->setDispatcher($dispatcher);
    }

    public function setOptions(array $options)
    {
        $this->setHost($options['host']);
        $this->setUser($options['user']);
        $this->setPass($options['pass']);
        $this->setPath($options['path']);
    }

    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function setUser($username)
    {
        $this->username = $username;

        return $this;
    }

    public function getUser()
    {
        return $this->username;
    }

    public function setPass($password)
    {
        $this->password = $password;

        return $this;
    }

    public function getPass()
    {
        return $this->password;
    }

    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;

        return $this;
    }

    /**
     * Checks if a file or directory exists on the remote server
     *
     * @param  string $path remote source path
     * @return bool   true when the resource exists, false otherwise
     */
    abstract public function exists($path);

    /**
     * Create a directory on the remote server
     *
     * @param string $dest remote path
     */
    abstract public function mkdir($dest);

    /**
     * Retrieve file or directory from remote server
     *
     * @param string $src  remote source path
     * @param string $dest (optional) local destination path
     */
    abstract public function get($src, $dest = null);

    /**
     * Upload a file or directory to remote server
     *
     * @param string $src  local source path
     * @param string $dest remote destination path
     */
    abstract public function put($src, $dest);

    /**
     * Upload a string to remote server
     *
     * @param string $content content
     * @param string $dest    remote destination path
     */
    abstract public function putContent($content, $dest);

    /**
     * Creates a symlink on the remote server
     *
     * @param $src
     * @param $dest
     * @return mixed
     */
    abstract public function symlink($src, $dest);

    /**
     * Copies a file/directory on the remote host
     *
     * @param  string $src
     * @param  string $dest
     * @param  bool   $recursive
     * @return mixed
     */
    abstract public function copy($src, $dest, $recursive = true);

    /**
     * Removes a file/directory on the remote host
     *
     * @param  string $path
     * @param  bool   $recursive
     * @return mixed
     */
    abstract public function remove($path, $recursive = true);
}
