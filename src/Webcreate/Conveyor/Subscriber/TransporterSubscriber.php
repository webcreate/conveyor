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

use Webcreate\Conveyor\Event\TransporterEvents;
use Webcreate\Conveyor\Transporter\FileTransporter;
use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\Event\TransporterEvent;

class TransporterSubscriber implements EventSubscriberInterface
{
    protected $io;

    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    public static function getSubscribedEvents()
    {
        return array(
            TransporterEvents::TRANSPORTER_CONNECT     => array('onTransporterConnect'),
            TransporterEvents::TRANSPORTER_PUT         => array('onTransporterPut'),
            TransporterEvents::TRANSPORTER_PUT_CONTENT => array('onTransporterPutContent'),
            TransporterEvents::TRANSPORTER_GET         => array('onTransporterGet'),
            TransporterEvents::TRANSPORTER_MKDIR       => array('onTransporterMkdir'),
            TransporterEvents::TRANSPORTER_SYMLINK     => array('onTransporterSymlink'),
            TransporterEvents::TRANSPORTER_COPY        => array('onTransporterCopy'),
            TransporterEvents::TRANSPORTER_REMOVE      => array('onTransporterRemove'),
        );
    }

    public function onTransporterConnect(TransporterEvent $event)
    {
        if (false === $this->io->isVerbose()) return;

        $this->io->write(
            sprintf(
                'Connecting to <comment>%s@%s</comment>...',
                $event->getTransporter()->getUser(),
                $event->getTransporter()->getHost()
            )
        );
    }

    public function onTransporterGet(TransporterEvent $event)
    {
        if (false === $this->io->isVerbose()) return;

        $verb = 'Downloading';
        if ($event->getTransporter() instanceof FileTransporter) {
            $verb = 'Reading';
        }

        $data = $event->getData();

        $this->io->write(
            sprintf(
                '%s <comment>%s</comment>',
                $verb,
                $data['src']
            )
        );
    }

    public function onTransporterPut(TransporterEvent $event)
    {
        $verb = 'Uploading';
        if ($event->getTransporter() instanceof FileTransporter) {
            $verb = 'Copying';
        }

        $data = $event->getData();

        $this->io->write(
            sprintf(
                '%s <comment>%s</comment> to <comment>%s</comment>',
                $verb,
                $data['src'],
                $data['dest']
            )
        );
    }

    public function onTransporterPutContent(TransporterEvent $event)
    {
        $verb = 'Writing';

        $data = $event->getData();

        $this->io->write(
            sprintf(
                '%s content to <comment>%s</comment>',
                $verb,
                $data['dest']
            )
        );
    }

    public function onTransporterMkdir(TransporterEvent $event)
    {
        if (false === $this->io->isVerbose()) return;

        $data = $event->getData();

        $this->io->write(
            sprintf(
                'Creating directory <comment>%s</comment>',
                $data
            )
        );
    }

    public function onTransporterSymlink(TransporterEvent $event)
    {
        $data = $event->getData();

        $this->io->write(
            sprintf(
                'Symlinking <comment>%s</comment> to <comment>%s</comment>',
                $data['src'],
                $data['dest']
            )
        );
    }

    public function onTransporterCopy(TransporterEvent $event)
    {
        $data = $event->getData();

        $this->io->write(
            sprintf(
                'Copying (server-side) <comment>%s</comment> to <comment>%s</comment>',
                $data['src'],
                $data['dest']
            )
        );
    }

    public function onTransporterRemove(TransporterEvent $event)
    {
        $data = $event->getData();

        $this->io->write(
            sprintf(
                'Removing (server-side) <comment>%s</comment>',
                $data['path']
            )
        );
    }
}
