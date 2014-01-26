<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Strategy;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webcreate\Conveyor\Context;
use Webcreate\Conveyor\DependencyInjection\TransporterAwareInterface;
use Webcreate\Conveyor\Event\StageEvent;
use Webcreate\Conveyor\Event\StageEvents;
use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\Repository\Version;
use Webcreate\Conveyor\Transporter\AbstractTransporter;
use Webcreate\Conveyor\Transporter\ReadOnlyTransporter;
use Webcreate\Conveyor\Transporter\TransactionalTransporterInterface;
use Webcreate\Conveyor\Util\FileCollection;
use Webcreate\Conveyor\Util\FilePath;

class ReleasesStrategy implements StrategyInterface, TransporterAwareInterface, EventSubscriberInterface
{
    /**
     * @var AbstractTransporter
     */
    protected $transporter;

    protected $options;
    protected $io;

    protected $sharedFiles;
    protected $uploadPath;

    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    /**
     * Sets options
     *
     * @param array $options
     * @return mixed
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * Sets transporter
     *
     * @param $transporter
     * @throws \InvalidArgumentException
     */
    public function setTransporter($transporter)
    {
        if (
            $transporter instanceof TransactionalTransporterInterface
            && !$transporter instanceof ReadOnlyTransporter
        ) {
            throw new \InvalidArgumentException(sprintf('Transporter "%s" is not supported by the releases strategy', get_class($transporter)));
        }

        $this->transporter = $transporter;
    }

    /**
     * Returns an array contain the required directories relative
     * to the target's basepath
     *
     * @return string[]
     */
    public function getRequiredDirectories()
    {
        return array(
            'releases',
            'shared',
        );
    }

    /**
     * Returns the relative path to the current release
     *
     * @return string
     */
    public function getCurrentReleasePath()
    {
        return 'current';
    }

    /**
     * Returns the upload path for a specific version. Adds
     * a suffix if the path already exists (can happen with a
     * full deploy)
     *
     * @param  \Webcreate\Conveyor\Repository\Version $version
     * @return mixed
     */
    public function getUploadPath(Version $version)
    {
        $basepath = $this->transporter->getPath();

        if (null === $this->uploadPath) {
            $suffix = '';
            $count = 0;

            do {
                $releasePath = 'releases/' . $version->getName() . '-' . substr($version->getBuild(), 0, 6) . $suffix;
                $suffix = '_' . ++$count;
            } while ($this->transporter->exists($basepath . '/' . $releasePath));

            $this->uploadPath = $releasePath;
        }

        return $this->uploadPath;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            StageEvents::STAGE_PRE_EXECUTE => 'onStagePreExecute',
            StageEvents::STAGE_POST_EXECUTE => 'onStagePostExecute',
        );
    }

    public function onStagePreExecute(StageEvent $e)
    {
        if ('deploy.before' === $e->getStageName()) {
            $this->uploadPath = null; // reset the cached uploadPath

            $this->prepareUploadPath($e->getContext());
            $this->prepareSharedFilesAndFolders($e->getContext());
        } elseif ('transfer' === $e->getStageName()) {
            $this->filterSharedFilesAndFolders($e->getContext());
        }
    }

    public function onStagePostExecute(StageEvent $e)
    {
        if ('deploy.after' === $e->getStageName()) {
            $this->updateCurrentReleasePathSymlink($e->getContext());
        } elseif ('transfer' === $e->getStageName()) {
            $this->symlinkSharedFilesAndFolders($e->getContext());

            if (count($this->sharedFiles) > 0) {
                $this->putDeferedSharedFiles($this->sharedFiles, $e->getContext());
            }
        } elseif ('deploy.final' === $e->getStageName()) {
            $this->cleanupOldReleases($this->options['keep']);
        }
    }

    /**
     * Removes old releases
     *
     * @param $keep
     */
    protected function cleanupOldReleases($keep)
    {
        $basepath   = $this->transporter->getPath();
        $uploadPath = $basepath . '/releases';
        $transporter = $this->transporter;

        $this->io->write('Searching for outdated releases');
        $this->io->increaseIndention(1);

        $files = $transporter->ls($uploadPath);

        $this->array_qsort2($files, 'mtime', $order='DESC');

        $removableFiles = array();
        $i = 0;

        foreach ($files as $file => $info) {
            if ($i > $keep) {
                $removableFiles[] = $file;
            }

            $i++;
        }

        if (0 === count($removableFiles)) {
            $this->io->write('None found');
        }

        // if it's only 1 directory to remove, just remove it without asking
        // for permission of the user
        if (1 === count($removableFiles)) {
            $file = array_pop($removableFiles);

            // use safe guard, because transport can throw an exception in case
            // of a permission denied
            $this->safeGuard(
                function() use ($transporter, $uploadPath, $file) {
                    $transporter->remove($uploadPath . '/' . $file, true);
                }
            );
        }

        // More then 1 file to remove? Let's inform the used and ask for permission.
        // This can happen if the user decides to turn this feature on after some
        // deploys have already been done.
        if (count($removableFiles) > 0) {
            $askAgain = true;

            while ($askAgain) {
                $askAgain = false; // don't ask again on default

                $answer = $this->io->select(
                    sprintf('Found %d out of %d releases which can be removed, would you like to remove them?', count($removableFiles), count($files)),
                    array(
                        'y' => 'remove outdated releases step by step',
                        'a' => 'remove all outdated releases at once',
                        'n' => 'abort and let you manually clean things up',
                        'v' => 'view outdated release folders',
                    )
                );

                switch ($answer) {
                    case 'y':
                        while($file = array_pop($removableFiles)) {
                            if ($this->io->askConfirmation(sprintf('Would you like to remove %s? [Y/n] ', $file), true)) {
                                // use safe guard, because transport can throw an exception in case
                                // of a permission denied
                                $this->safeGuard(
                                    function() use ($transporter, $uploadPath, $file) {
                                        $transporter->remove($uploadPath . '/' . $file, true);
                                    }
                                );
                            } else {
                                $askAgain = true;
                                break;
                            }
                        }
                        break;

                    case 'a':
                        // remove all in reversed order (oldest first)
                        while($file = array_pop($removableFiles)) {
                            // use safe guard, because transport can throw an exception in case
                            // of a permission denied
                            $this->safeGuard(
                                function() use ($transporter, $uploadPath, $file) {
                                    $transporter->remove($uploadPath . '/' . $file, true);
                                }
                            );
                        }
                        break;

                    case 'n':
                        break;

                    case 'v':
                    default:
                        $askAgain = true;

                        foreach ($removableFiles as $file) {
                            $info = $files[$file];

                            $this->io->write(sprintf('[%s] <comment>%s</comment>', $info['mtime']->format('Y-m-d H:i:s'), $uploadPath . '/' . $file));

                            $i++;
                        }
                        break;
                }
            }
        }

        $this->io->decreaseIndention(1);
    }

    /**
     * Catches exceptions and asks if you want to retry
     *
     * @param callable $operation
     */
    protected function safeGuard($operation)
    {
        while (true) {
            try {
                $operation();

                break;
            } catch (\Exception $e) {
                $this->io->renderException($e);

                if (false === $this->tryAgain()) {
                    // skip
                    break;
                }
            }
        }
    }

    protected function tryAgain()
    {
        while (true) {
            $answer = $this->io->select(
                '<info>Select an action</info>',
                array(
                    'a' => 'abort',
                    'r' => 'retry this operation',
                    's' => 'skip this operation and continue with the next',
                ),
                'r'
            );

            switch ($answer) {
                case "a":
                    $this->io->setIndention(0);
                    $this->io->write('Aborted.');
                    die();
                    break;
                case "r":
                    return true;
                    break;
                case "s":
                    return false;
                    break;
            }
        }

        return true;
    }

    /*
     * Sort a multidimensional array on a column
     *
     * For example:
     * <code>
     * <?php array_qsort2($users, "username", "ASC"); ?>
     * </code>
     *
     * @param array $array array with hash array
     * @param mixed $column key that you want to sort on
     * @param enum $order asc or desc
     */
    protected function array_qsort2(&$array, $column=0, $order="ASC")
    {
        $oper = ($order == "ASC")?">":"<";

        if(!is_array($array)) return;

        uasort($array, create_function('$a,$b',"return (\$a['$column'] $oper \$b['$column']);"));

        reset($array);
    }

    /**
     *
     * @param FileCollection $sharedFiles
     * @param Context $context
     */
    protected function putDeferedSharedFiles($sharedFiles, Context $context)
    {
        $basepath   = $this->transporter->getPath();
        $sharedPath = $basepath . '/shared';

        foreach($sharedFiles as $file) {
            $src = FilePath::join($context->getBuilddir(), $file);
            $sharedFilepath = $sharedPath . '/' . $file;

            if (false === file_exists($src)) {
                // @todo we might wanna ask the user if he likes to continue or abort
                if ($this->io) {
                    $this->io->write(sprintf('<error>Warning</error> <comment>%s</comment> not found', $src));
                }

                continue;
            }

            $this->transporter->put($src, $sharedFilepath);
        }
    }

    /**
     * Symlinks the shared locations
     *
     * @param Context $context
     */
    protected function symlinkSharedFilesAndFolders(Context $context)
    {
        $basepath           = $this->transporter->getPath();
        $sharedPath         = $basepath . '/shared';
        $uploadPath         = $basepath . '/' . $this->getUploadPath($context->getVersion());

        $shared = (array) $this->options['shared'];

        // add some white space to the output
        if (count($shared) > 0) {
            $this->io->write('');
        }

        foreach ($shared as $fileOrFolder) {
            $sharedFilepath = $sharedPath . '/' . $fileOrFolder;
            $uploadFilepath = $uploadPath . '/' . $fileOrFolder;

            // make sure the symlink destination doesn't exist
            if (true === $this->transporter->exists($uploadFilepath)) {
                // Ok, it exists, but it's a symlink... let's assume that it was created
                // in an earlier deploy with conveyor
                if (true === $this->transporter->isSymlink($uploadFilepath)) {
                    continue;
                }

                $answer = $this->io->askConfirmation(
                    sprintf('<error>Warning</error> Shared file/folder <info>%s</info> already exists, do you want to overwrite it? (n/Y): ', $uploadFilepath),
                    false
                );

                if ($answer) {
                    $this->transporter->remove($uploadFilepath, true);
                } else {
                    continue;
                }
            }

            $this->transporter->symlink($sharedFilepath, $uploadFilepath);
        }
    }

    /**
     * Makes sure we don't upload to locations that are shared
     *
     * @param Context $context
     */
    protected function filterSharedFilesAndFolders(Context $context)
    {
        $this->sharedFiles = new FileCollection($context->getBuilddir());

        $filesModified = $context->getFilesModified();

        $shared = (array) $this->options['shared'];
        foreach ($shared as $fileOrFolder) {
            if ($filesModified->has($fileOrFolder)) {
                $filesModified->remove($fileOrFolder);

                $this->sharedFiles->add($fileOrFolder);
            }
        }
    }

    /**
     * Updates the symlink for the current release
     *
     * @param Context $context
     */
    protected function updateCurrentReleasePathSymlink(Context $context)
    {
        $basepath = $this->transporter->getPath();

        // add some white space to the output
        $this->io->write('');

        $this->transporter->symlink(
            $basepath . '/' . $this->getUploadPath($context->getVersion()),
            $basepath . '/' . $this->getCurrentReleasePath()
        );
    }

    /**
     * Copies the latest version server-side to the uploaddir,
     * in case this is a incremental deploy.
     *
     * @param \Webcreate\Conveyor\Context $context
     */
    protected function prepareUploadPath($context)
    {
        $basepath           = $this->transporter->getPath();
        $uploadPath         = $basepath . '/' . $this->getUploadPath($context->getVersion());
        $currentReleasePath = $basepath . '/' . $this->getCurrentReleasePath();

        if (false === $context->isFullDeploy()) {
            // add some white space to the output
            $this->io->write('');

            $this->transporter->copy(
                $currentReleasePath,
                $uploadPath
            );
        }
    }

    /**
     * Creates shared files and folders
     */
    protected function prepareSharedFilesAndFolders(Context $context)
    {
        $basepath   = $this->transporter->getPath();
        $sharedPath = $basepath . '/shared';

        $shared = (array) $this->options['shared'];

        // add some white space to the output
        if (count($shared) > 0) {
            $this->io->write('');
        }

        foreach ($shared as $fileOrFolder) {
            $sharedFilepath = $sharedPath . '/' . $fileOrFolder;
            $localFilepath = $context->getBuilddir() . '/' . $fileOrFolder;

            if (false === $this->transporter->exists($sharedFilepath)) {
                // Hmm, the shared entity doesn't exist

                // is it a directory?
                if (is_dir($localFilepath) || '/' === substr($sharedFilepath, -1)) {
                    $this->transporter->mkdir($sharedFilepath);
                } else {
                    $parentDir = dirname($sharedFilepath);
                    if (false === $this->transporter->exists($parentDir) && $parentDir != $sharedPath) {
                        $this->transporter->mkdir($parentDir);
                    }
                    $this->transporter->putContent('', $sharedFilepath); // make a dummy file
                }
            }
        }
    }
}
