<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Stage\Manager;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webcreate\Conveyor\Context;
use Webcreate\Conveyor\Event\StageEvent;
use Webcreate\Conveyor\Event\StageEvents;
use Webcreate\Conveyor\Stage\AbstractStage;

class StageManager
{
    protected $context;
    protected $stages = array();
    protected $dispatcher;

    public function __construct(Context $context, EventDispatcherInterface $dispatcher = null)
    {
        $this->context = $context;
        $this->dispatcher = $dispatcher;
    }

    public function addStage($name, AbstractStage $stage)
    {
        $this->stages[$name] = array('name' => $name, 'stage' => $stage);

        return $this;
    }

    /**
     * Returns the registered stages
     *
     * @return array[]
     */
    public function getStages()
    {
        return $this->stages;
    }

    public function execute(array $stages = array())
    {
        if (0 === count($stages)) {
            $stages = array_keys($this->stages);
        }

        foreach ($this->stages as $stageInfo) {
            if (false === in_array($stageInfo['name'], $stages)) {
                continue;
            }

            /** @var $stage AbstractStage */
            $stage = $stageInfo['stage'];

            if ($stage->supports($this->context)) {
                $this->dispatch(StageEvents::STAGE_PRE_EXECUTE, new StageEvent($stageInfo['name'], $stage, $this->context));

                $result = $stage->execute($this->context);

                $this->dispatch(StageEvents::STAGE_POST_EXECUTE, new StageEvent($stageInfo['name'], $stage, $this->context));

                if (false === $result) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function dispatch($eventName, Event $event = null)
    {
        if (null !== $this->dispatcher) {
            $this->dispatcher->dispatch($eventName, $event);
        }
    }
}
