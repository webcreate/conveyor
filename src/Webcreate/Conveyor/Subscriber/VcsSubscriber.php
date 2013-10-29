<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Vcs\Common\Events\VcsEvent;
use Webcreate\Vcs\Common\VcsEvents;

class VcsSubscriber implements EventSubscriberInterface
{
    protected $io;

    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    public static function getSubscribedEvents()
    {
        return array(
            VcsEvents::PRE_CHECKOUT => array('onVcsPreCheckout'),
            VcsEvents::PRE_EXPORT => array('onVcsPreExport'),
        );
    }

    public function onVcsPreCheckout(VcsEvent $event)
    {
        if (false === $this->io->isVerbose()) return;

        $data = $event->getData();

        $this->io->write(
            sprintf(
                'Checking out <comment>%s</comment> to <comment>%s</comment>...'
                ,
                @$data['head'],
                @$data['dest']
            )
        );
    }

    public function onVcsPreExport(VcsEvent $event)
    {
        if (false === $this->io->isVerbose()) return;

        $data = $event->getData();

        $this->io->write(
            sprintf(
                'Exporting <comment>%s</comment> to <comment>%s</comment>...'
                ,
                @$data['head'],
                @$data['dest']
            )
        );
    }
}
