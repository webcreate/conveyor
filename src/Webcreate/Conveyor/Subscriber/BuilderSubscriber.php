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

use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Webcreate\Conveyor\Event\BuilderEvents;
use Webcreate\Conveyor\IO\IOInterface;

class BuilderSubscriber implements EventSubscriberInterface
{
    protected $io;
    protected $showProgress = false;
    protected $needsNewline = false;

    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    public static function getSubscribedEvents()
    {
        return array(
            BuilderEvents::BUILDER_PRE_BUILD  => array('onBuilderPreBuild'),
            BuilderEvents::BUILDER_POST_BUILD => array('onBuilderPostBuild'),
            BuilderEvents::BUILDER_PRE_TASK   => array('onBuilderPreTask'),
            BuilderEvents::BUILDER_POST_TASK  => array('onBuilderPostTask'),
        );
    }

    public function onBuilderPreBuild(Event $event)
    {
        if (true === $this->showProgress) {
            $this->io->write(sprintf('- Executing tasks'));
            $this->io->increaseIndention(2);

            $this->io->write(sprintf('Progress: <comment>%d%%</comment>', 0), false);
        }
    }

    public function onBuilderPostBuild(Event $event)
    {
        if (true === $this->needsNewline) {
            $this->io->write('');
            $this->needsNewline = false;
        }

        if (true === $this->showProgress) {
            $this->io->decreaseIndention(2);
        }
    }

    public function onBuilderPreTask(GenericEvent $event)
    {
        if (true === $this->showProgress) return;

        $task = $event->getSubject();
        $io   = $this->io;

        $self = $this;

        $task->setOutput(function($output) use ($io, $self) {
            if ($io->isVerbose()) {
                $io->write(sprintf('%s', $output));
                $self->needsNewline = false;
            } else {
                $io->overwrite(sprintf('%s', $output), false);
                $self->needsNewline = true;
            }
        });

        if ($event->getArgument('index') > 0) {
            if (true === $this->needsNewline) {
                $this->io->write('');
                $this->needsNewline = false;
            }
            $this->io->write('');
        }

        $this->io->write(sprintf('- Executing task <info>%s</info>', get_class($task)));
        $this->io->increaseIndention(2);
    }

    public function onBuilderPostTask(GenericEvent $event)
    {
        $task = $event->getSubject();

        if (true === $this->showProgress) {
            $index = $event->getArgument('index');
            $total = $event->getArgument('total');
            $percentage = (++$index * 100 / $total);

            $this->io->overwrite(sprintf('Progress: <comment>%d%%</comment>', $percentage), false);

            $this->needsNewline = true;
        } else {
            $this->io->decreaseIndention(2);
        }
    }
}
