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
use Webcreate\Conveyor\DependencyInjection\TransporterAwareInterface;
use Webcreate\Conveyor\Event\StageEvent;
use Webcreate\Conveyor\Event\StageEvents;
use Webcreate\Conveyor\Repository\Version;
use Webcreate\Conveyor\Transporter\AbstractTransporter;

class ReleasesStrategy implements StrategyInterface, TransporterAwareInterface, EventSubscriberInterface
{
    /**
     * @var AbstractTransporter
     */
    protected $transporter;

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
     * Returns the upload path for a specific version
     *
     * @param  \Webcreate\Conveyor\Repository\Version $version
     * @return mixed
     */
    public function getUploadPath(Version $version)
    {
        return 'releases/' . $version->getName() . '-' . substr($version->getBuild(), 0, 6);
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

    protected function updateCurrentReleasePathSymlink($context)
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

        // remove the uploadPath if the already exists, eg. after a previous aborted deploy
        // this solves issues with copying, when the target dir (uploadPath) already exists, like
        // copying into a subfolder of uploadPath..
        if ($this->transporter->exists($uploadPath)) {
            $this->transporter->remove($uploadPath);
        }

        if (false === $context->isFullDeploy()) {
            $this->transporter->copy(
                $currentReleasePath,
                $uploadPath
            );
        }
    }

    public function onStagePreExecute(StageEvent $e)
    {
        if ('deploy.before' === $e->getStageName()) {
            $this->prepareUploadPath($e->getContext());
        }
    }

    public function onStagePostExecute(StageEvent $e)
    {
        if ('deploy.after' === $e->getStageName()) {
            $this->updateCurrentReleasePathSymlink($e->getContext());
        }
    }
}
