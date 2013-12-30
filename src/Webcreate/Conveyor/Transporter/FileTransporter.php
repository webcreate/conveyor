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
use Symfony\Component\Finder\Finder;

use Symfony\Component\Finder\SplFileInfo;
use Webcreate\Conveyor\Event\TransporterEvent;
use Webcreate\Conveyor\Event\TransporterEvents;

class FileTransporter extends AbstractTransporter
{
    public function getHost()
    {
        return 'localhost';
    }

    public function exists($path)
    {
        return file_exists($path);
    }

    public function mkdir($dest)
    {
        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_MKDIR, new TransporterEvent($this, $dest));

        mkdir($dest, 0777, true);
    }

    public function get($src, $dest = null)
    {
        if (is_file($src) && is_dir($dest)) {
            $dest = $dest . DIRECTORY_SEPARATOR . basename($src);
        }

        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_GET, new TransporterEvent($this, array('src' => $src, 'dest' => $dest)));

        if (null === $dest) {
            return file_get_contents($src);
        } else {
            if (is_file($src)) {
                $success = copy($src, $dest);
            } else {
                $filesystem = new Filesystem();
                $filesystem->mirror($src, $dest);
            }
        }
    }

    public function put($src, $dest)
    {
        if (false === file_exists($src)) {
            throw new \InvalidArgumentException(sprintf('Resource \'%s\' does not exist', $src));
        }

        if (is_file($src) && is_dir($dest)) {
            $dest = $dest . DIRECTORY_SEPARATOR . basename($src);
        }

        if (is_file($src)) {
            $file = new \SplFileInfo($src);

            $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_PUT, new TransporterEvent($this, array('dest' => $dest, 'src' => $file->getPathname())));

            $pwd = dirname($dest);
            if (!is_dir($pwd)) {
                $this->mkdir($pwd);
            }

            copy($file->getPathname(), $dest);
        } else {
            if (!$this->exists($dest)) {
                $this->mkdir($dest);
            }

            $finder = new Finder();
            $test = $finder->in($src)->depth('== 0');

            foreach ($test as $file) {
                $this->put(rtrim($src, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file->getFilename(), rtrim($dest, DIRECTORY_SEPARATOR)  . DIRECTORY_SEPARATOR . $file->getFilename());
            }
        }
    }

    public function putContent($content, $dest)
    {
        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_PUT_CONTENT, new TransporterEvent($this, array('dest' => $dest, 'content' => $content)));

        file_put_contents($dest, $content);
    }

    /**
     * Creates a symlink on the remote server
     *
     * @param string $src
     * @param string $dest
     * @return mixed
     */
    public function symlink($src, $dest)
    {
        $dest = rtrim($dest, '/');

        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_SYMLINK, new TransporterEvent($this, array('dest' => $dest, 'src' => $src)));

        $filesystem = new Filesystem();
        $filesystem->symlink($src, $dest);
    }

    /**
     * Checks for symlink on the remote server
     *
     * @param $dest
     * @return bool
     */
    public function isSymlink($dest)
    {
        return is_link($dest);
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

    /**
     * Lists files and directories
     *
     * @param  string $path
     * @return mixed
     */
    public function ls($path)
    {
        $finder = new Finder();
        $test = $finder->in($path)->depth('== 0');

        $retval = array();

        /** @var $file SplFileInfo */
        foreach ($test as $file) {
            $retval[$file->getFilename()] = array(
                'type' => $file->isDir() ? 'directory' : 'file',
                'mtime' => new \DateTime('@' . $file->getMTime()),
            );
        }

        return $retval;
    }
}
