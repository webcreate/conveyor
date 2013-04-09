<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Transporter;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Finder\Finder;

use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\Transporter\Ftp\Sftp;
use Webcreate\Conveyor\Event\TransporterEvent;
use Webcreate\Conveyor\Event\TransporterEvents;

/**
 * @todo refactor the injection of the IOInterface to a event-based solution?
 */
class SftpTransporter extends AbstractTransporter implements SshCapableTransporterInterface
{
    /**
     * @var null|Ftp\Sftp|\Net_SFTP
     */
    protected $sftp;
    protected $io;

    public function __construct(EventDispatcherInterface $dispatcher, $sftp = null, IOInterface $io)
    {
        parent::__construct($dispatcher);

        if (null === $sftp) {
            $sftp = new Sftp();
        }
        $this->sftp = $sftp;
        $this->io = $io;
    }

    public function connect()
    {
        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_CONNECT, new TransporterEvent($this));

        $success = $this->sftp->connect($this->host);

        if (false === $success) {
            throw new \RuntimeException(sprintf('Could not connect to host %s', $this->host));
        }
    }

    /**
     * @return bool
     */
    public function login()
    {
        $password = $this->password;
        $username = $this->username;

        // try to login using ssh key
        if (null === $password) {
            $identityFilePath = $_SERVER['HOME'] . '/.ssh/id_rsa';
            if (false !== file_exists($identityFilePath)) {
                $identityFile = file_get_contents($identityFilePath);

                // try without pass
                $key = new \Crypt_RSA();
                $loaded = $key->loadKey($identityFile);

                if (!$loaded || !$this->sftp->login($username, $key)) {
                    $attempts = 3;

                    while ($attempts--) {
                        // retry with password
                        $key->setPassword($this->io->askAndHideAnswer(sprintf('Enter passphrase for %s: ', $identityFilePath)));
                        $loaded = $key->loadKey($identityFile);

                        if ($loaded && $success = $this->sftp->login($username, $key)) {
                            return $success;
                        } else {
                            if ($attempts > 0) {
                                $this->io->write('Permission denied, please try again.');
                            }
                        }
                    }

                    if (!$loaded || !$this->sftp->login($username, $key)) {
                        return false;
                    }
                }
            }

            return false;
        }

        // login with given password
        return $this->sftp->login($username, $password);
    }

    public function connectAndLogin()
    {
        $this->connect();
        $success = $this->login();

        if (false === $success) {
            throw new \Exception(sprintf('Unable to login to %s', $this->host));
        }
    }

    public function exists($path)
    {
        if (false === $this->isConnected()) {
            $this->connectAndLogin();
        }

        $pwd = $this->sftp->pwd();

        // try to change directory to see if it is an existing directory
        $result = $this->sftp->chdir($path);
        if (true === $result) {
            $result = $this->sftp->chdir($pwd); // change back to the original directory

            return true;
        } else {
            // list the parent directory and check if the file exists
            $parent = dirname($path);
            $result = $this->sftp->nlist($parent);
            if (false !== $result) {
                if (in_array(basename($path), $result)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function mkdir($dest)
    {
        if (false === $this->isConnected()) {
            $this->connectAndLogin();
        }

        $pwd = dirname($dest);
        if (false === $this->exists($pwd)) {
            $this->mkdir($pwd);
        }

        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_MKDIR, new TransporterEvent($this, $dest));

        $success = $this->sftp->mkdir($dest);
        if (false === $success) {
            throw new \RuntimeException('Something went wrong: ' . "\n" . implode("\n", $this->sftp->getSFTPErrors()));
        }
    }

    public function get($src, $dest = null)
    {
        if (false === $this->isConnected()) {
            $this->connectAndLogin();
        }

        $realDest = $dest;

        if (null == $dest) {
            $realDest = tempnam(sys_get_temp_dir(), basename($src));
        }

        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_GET, new TransporterEvent($this, array('src' => $src, 'dest' => $dest)));

        $succes = $this->sftp->get($src, $realDest);

        if (null == $dest) {
            $content = file_get_contents($realDest);
            unlink($realDest);

            return $content;
        }
    }

    public function put($src, $dest)
    {
        if (false === $this->isConnected()) {
            $this->connectAndLogin();
        }

        if (false === file_exists($src)) {
            throw new \InvalidArgumentException(sprintf('Resource \'%s\' does not exist', $src));
        }

        if (is_file($src)) {
            $file = new \SplFileInfo($src);

            $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_PUT, new TransporterEvent($this, array('dest' => $dest, 'src' => $file->getPathname())));

            $success = $this->sftp->put($dest, $file->getPathname(), NET_SFTP_LOCAL_FILE);

            if (!$success) {
                // maybe the parent directory doesnt exist; try to create it and try again
                $pwd = dirname($dest);
                if (false === $this->exists($pwd)) {
                    $this->mkdir($pwd);

                    // retry try to put
                    $success = $this->sftp->put($dest, $file->getPathname(), NET_SFTP_LOCAL_FILE);
                    if (!$success) {
                        throw new \RuntimeException('Something went wrong: ' . "\n" . implode("\n", $this->sftp->getSFTPErrors()));
                    }
                }
            }
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
        if (false === $this->isConnected()) {
            $this->connectAndLogin();
        }

        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_PUT_CONTENT, new TransporterEvent($this, array('dest' => $dest, 'content' => $content)));

        $this->sftp->put($dest, $content, NET_SFTP_STRING);
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
        if (false === $this->isConnected()) {
            $this->connectAndLogin();
        }
        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_SYMLINK, new TransporterEvent($this, array('dest' => $dest, 'src' => $src)));

        // make src an absolute path
        if (0 !== strpos($src, '/')) {
            $src = $this->sftp->pwd() . '/' . $src;
        }

        $success = $this->sftp->exec(sprintf("ln -s -T -f %s %s", escapeshellarg($src), escapeshellarg($dest)));
        if (false === $success) {
            throw new \RuntimeException('Something went wrong: ' . "\n" . implode("\n", $this->sftp->getErrors()));
        }
    }

    /**
     * Copies a file/directory on the remote host
     *
     * @param string $src
     * @param string $dest
     * @param bool $recursive
     * @throws \RuntimeException
     * @return mixed
     */
    public function copy($src, $dest, $recursive = true)
    {
        if (false === $this->isConnected()) {
            $this->connectAndLogin();
        }

        $this->dispatcher->dispatch(TransporterEvents::TRANSPORTER_COPY, new TransporterEvent($this, array('dest' => $dest, 'src' => $src)));

        $recursiveFlag = ($recursive ? 'r' : '');

        // adjust for symlink
        // we want to copy the contents of the symlink (dereference it), but keep links in subfolders
        $lstat = $this->sftp->lstat($src);
        if ($lstat['type'] === 3) {
            $result = $this->sftp->exec(sprintf("readlink -f %s", escapeshellarg($src)));
            if (false === $result) {
                throw new \RuntimeException('Something went wrong: ' . "\n" . implode("\n", $this->sftp->getErrors()));
            }

            $src = trim($result);
        }

        $success = $this->sftp->exec(sprintf("cp -{$recursiveFlag}f %s %s", escapeshellarg($src), escapeshellarg($dest)));
        if (false === $success) {
            throw new \RuntimeException('Something went wrong: ' . "\n" . implode("\n", $this->sftp->getErrors()));
        }
    }

    protected function isConnected()
    {
        return $this->sftp->isConnected();
    }

    public function __destruct()
    {
        if ($this->sftp) {
            $this->sftp->disconnect();
        }
    }
}
