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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Webcreate\Conveyor\Event\TaskRunnerEvents;
use Webcreate\Conveyor\IO\IOInterface;

class TaskRunnerSubscriber implements EventSubscriberInterface
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
            TaskRunnerEvents::TASKRUNNER_PRE_EXECUTE_TASK  => array('onTaskPreExecute'),
            TaskRunnerEvents::TASKRUNNER_POST_EXECUTE_TASK  => array('onTaskPostExecute'),
        );
    }

    public function onTaskPreExecute(GenericEvent $event)
    {
        $task = $event->getSubject();
        $io   = $this->io;

        $self = $this;

        $task->setOutput(function($output) use ($io, $self) {
            $io->overwrite(sprintf('%s', $output), false);

            $self->needsNewline = true;
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

    public function onTaskPostExecute(GenericEvent $event)
    {
        $task = $event->getSubject();

        $this->io->write('');

        $this->io->decreaseIndention(2);
    }
}
