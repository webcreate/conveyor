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
use Webcreate\Conveyor\Transporter\AbstractTransporter;

class ReadOnlyTransporter extends AbstractTransporter implements SshCapableTransporterInterface, TransactionalTransporterInterface
{
    /**
     * @var AbstractTransporter
     */
    protected $innerTransporter;

    public function __construct(EventDispatcherInterface $dispatcher, AbstractTransporter $innerTransporter = null)
    {
        parent::__construct($dispatcher);

        if (null !== $innerTransporter) {
            $this->setInnerTransporter($innerTransporter);
        }
    }

    public function getHost()
    {
        return $this->innerTransporter->getHost();
    }

    public function getPath()
    {
        return $this->innerTransporter->getPath();
    }

    public function getUser()
    {
        return $this->innerTransporter->getUser();
    }

    public function getPass()
    {
        return $this->innerTransporter->getPass();
    }

    public function setInnerTransporter(AbstractTransporter $transporter)
    {
        $this->innerTransporter = $transporter;
    }

    /**
     * Checks if a file or directory exists on the remote server
     *
     * @param  string $path remote source path
     * @return bool   true when the resource exists, false otherwise
     */
    public function exists($path)
    {
        return $this->innerTransporter->exists($path);
    }

    /**
     * Create a directory on the remote server
     *
     * @param string $dest remote path
     */
    public function mkdir($dest)
    {
        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_MKDIR, new TransporterEvent($this, $dest));
    }

    /**
     * Retrieve file or directory from remote server
     *
     * @param string $src  remote source path
     * @param string $dest (optional) local destination path
     */
    public function get($src, $dest = null)
    {
        return $this->innerTransporter->get($src, $dest);
    }

    /**
     * Upload a file or directory to remote server
     *
     * @param string $src  local source path
     * @param string $dest remote destination path
     */
    public function put($src, $dest)
    {
        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_PUT, new TransporterEvent($this, array('dest' => $dest, 'src' => $src)));
    }

    public function putContent($content, $dest)
    {
        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_PUT_CONTENT, new TransporterEvent($this, array('dest' => $dest, 'content' => $content)));
    }

    public function symlink($src, $dest)
    {
        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_SYMLINK, new TransporterEvent($this, array('dest' => $dest, 'src' => $src)));
    }

    /**
     * Checks for symlink on the remote server
     *
     * @param $dest
     * @return bool
     */
    public function isSymlink($dest)
    {
        return $this->innerTransporter->isSymlink($dest);
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
        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_COPY, new TransporterEvent($this, array('dest' => $dest, 'src' => $src)));
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
        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_REMOVE, new TransporterEvent($this, array('path' => $path)));
    }

    public function exec($command, $callback = null)
    {
        // nothing here
    }

    public function begin()
    {
        // nothing here
    }

    public function commit()
    {
        // nothing here
    }

    public function ls($path)
    {
        return $this->innerTransporter->ls($path);
    }
}
