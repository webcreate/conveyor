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
use Webcreate\Conveyor\Util\FileCollection;

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

    public function setTransporter($transporter)
    {
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
        }
    }

    /**
     * @param FileCollection $sharedFiles
     * @param Context $context
     */
    protected function putDeferedSharedFiles($sharedFiles, Context $context)
    {
        $basepath   = $this->transporter->getPath();
        $sharedPath = $basepath . '/shared';

        foreach($sharedFiles as $fileOrFolder) {
            $sharedFilepath = $sharedPath . '/' . $fileOrFolder;

            $answer = $this->io->askConfirmation(
                sprintf('<error>Warning</error> Would you like to create/overwrite the shared file/folder <info>%s</info> to <info>%s</info>? (n/Y): ', $fileOrFolder, $sharedFilepath),
                false
            );

            if ($answer) {
                $this->transporter->put($fileOrFolder, $sharedFilepath);
            }
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
        foreach ($shared as $fileOrFolder) {
            $sharedFilepath = $sharedPath . '/' . $fileOrFolder;
            $uploadFilepath = $uploadPath . '/' . $fileOrFolder;

            // make sure the symlink destination doesn't exist
            if (true === $this->transporter->exists($uploadFilepath)) {
                $answer = $this->io->askConfirmation(
                    sprintf('<error>Warning</error> Shared file/folder <info>%s</info> already exists, continue with removing it? (n/Y): ', $uploadFilepath),
                    false
                );

                if ($answer) {
                    $this->transporter->remove($uploadFilepath, true);
                } else {
                    $this->io->write('Aborted');
                    die();
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
            if ($filesModified->has($fileOrFolder, true)) {
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
        $uploadPath = $basepath . '/' . $this->getUploadPath($context->getVersion());

        $shared = (array) $this->options['shared'];

        foreach ($shared as $fileOrFolder) {
            $sharedFilepath = $sharedPath . '/' . $fileOrFolder;
            $uploadFilepath = $uploadPath . '/' . $fileOrFolder;

            if (false === $this->transporter->exists($sharedFilepath)) {
                // Hmm, the shared entity doesn't exist

                // is it a directory?
                if ('/' === substr($sharedFilepath, -1)) {
                    $this->transporter->mkdir($sharedFilepath);
                } else {
                    $this->transporter->mkdir(dirname($sharedFilepath));
                    $this->transporter->putContent('', $sharedFilepath); // make a dummy file
                }
            }
        }
    }
}
