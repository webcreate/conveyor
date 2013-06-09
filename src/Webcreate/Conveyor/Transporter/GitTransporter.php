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

use Symfony\Component\Filesystem\Filesystem;
use Webcreate\Conveyor\Event\TransporterEvents;
use Webcreate\Conveyor\Event\TransporterEvent;
use Webcreate\Vcs\Git;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @todo research if this transporter could extend the FileTransporter
 */
class GitTransporter extends AbstractTransporter implements TransactionalTransporterInterface
{
    protected $dispatcher;
    protected $git;
    protected $hasCheckout = false;
    protected $cwd;
    protected $userdir;

    protected $added = array();

    public function __construct(EventDispatcherInterface $dispatcher, $userdir)
    {
        $this->dispatcher = $dispatcher;
        $this->userdir = preg_replace('/^\~/', getenv('HOME'), $userdir);
    }

    public function setOptions(array $options)
    {
        $this->git = new Git($options['url']);
        $this->cwd = $this->userdir . '/git/' . md5($options['url']);

        // this is a little hack, other services/stages depend on the path
        $this->setPath($this->cwd);
    }

    protected function checkout()
    {
        if (false === is_dir($this->cwd)) {
            $this->git->checkout($this->cwd);
        } else {
            $this->git->setCwd($this->cwd);
            $this->git->pull();
        }

        $this->hasCheckout = true;
    }

    protected function hasCheckout()
    {
        return $this->hasCheckout;
    }

    public function begin()
    {
        $this->added = array();
    }

    public function commit()
    {
        if (0 === count($this->added)) {
            // nothing to commit
            return;
        }

        foreach ($this->added as $dest) {
            $result = $this->git->add($dest);
        }

        $this->git->commit('Commit by Conveyor');
        $this->git->push();
    }

    /**
     * Checks if a file or directory exists on the remote server
     *
     * @param  string $path remote source path
     * @return bool   true when the resource exists, false otherwise
     */
    public function exists($path)
    {
        if (false === $this->hasCheckout()) {
            $this->checkout();
        }

        return file_exists($path);
    }

    /**
     * Create a directory on the remote server
     *
     * @param string $dest remote path
     */
    public function mkdir($dest)
    {
        if (false === $this->hasCheckout()) {
            $this->checkout();
        }

        $pwd = dirname($dest);
        if (false === is_dir($pwd)) {
            $this->mkdir($pwd);
        }

        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_MKDIR, new TransporterEvent($this, $dest));

        mkdir($dest);
    }

    /**
     * Retrieve file or directory from remote server
     *
     * @param string $src  remote source path
     * @param string $dest (optional) local destination path
     */
    public function get($src, $dest = null)
    {
        if (false === $this->hasCheckout()) {
            $this->checkout();
        }

        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_GET, new TransporterEvent($this, array('src' => $src, 'dest' => $dest)));

        return file_get_contents($src);
    }

    /**
     * Upload a file or directory to remote server
     *
     * @param  string                    $src  local source path
     * @param  string                    $dest remote destination path
     * @throws \InvalidArgumentException
     */
    public function put($src, $dest)
    {
        if (false === file_exists($src)) {
            throw new \InvalidArgumentException(sprintf('Resource \'%s\' does not exist', $src));
        }

        if (false === $this->hasCheckout()) {
            $this->checkout();
        }

        $pwd = dirname($dest);
        if (false === is_dir($pwd)) {
            $this->mkdir($pwd);
        }

        $this->added[] = $dest;

        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_PUT, new TransporterEvent($this, array('dest' => $dest, 'src' => $src)));

        file_put_contents($dest, file_get_contents($src));
    }

    /**
     * Upload a string to remote server
     *
     * @param string $content content
     * @param string $dest    remote destination path
     */
    public function putContent($content, $dest)
    {
        if (false === $this->hasCheckout()) {
            $this->checkout();
        }

        $this->added[] = $dest;

        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_PUT_CONTENT, new TransporterEvent($this, array('dest' => $dest, 'content' => $content)));

        file_put_contents($dest, $content);
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
        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_COPY, new TransporterEvent($this, array('dest' => $dest, 'src' => $src)));

        $filesystem = new Filesystem();

        if ($recursive) {
            $filesystem->mirror($src, $dest);
        } else {
            $filesystem->copy($src, $dest);
        }
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

        $filesystem = new Filesystem();
        $filesystem->remove($path);
    }
}
