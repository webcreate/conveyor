<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Transporter;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

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
     * @param $src
     * @param $dest
     * @return mixed
     */
    public function symlink($src, $dest)
    {
        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_SYMLINK, new TransporterEvent($this, array('dest' => $dest, 'src' => $src)));

        $filesystem = new Filesystem();
        $filesystem->symlink($src, $dest);
    }

    /**
     * Copies a file/directory on the remote host
     *
     * @param string $src
     * @param string $dest
     * @param bool $recursive
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
}
