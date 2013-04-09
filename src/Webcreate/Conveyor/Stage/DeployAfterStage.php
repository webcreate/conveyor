<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Stage;

use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\Task\TaskRunner;
use Webcreate\Conveyor\Context;
use Webcreate\Conveyor\Stage\AbstractStage;

class DeployAfterStage extends AbstractStage
{
    protected $taskRunner;
    protected $io;

    public function __construct(TaskRunner $taskRunner, IOInterface $io)
    {
        $this->taskRunner = $taskRunner;
        $this->io = $io;
    }

    public function supports(Context $context)
    {
        return true;
    }

    public function execute(Context $context)
    {
        if (true === $this->taskRunner->hasTasks()) {
            $this->executeTaskrunner($context);
        }
    }

    protected function executeTaskrunner(Context $context)
    {
        $this->io->write('');

        if (true === $context->isSimulate()) {
            $this->io->write(sprintf('Simulating tasks after deploy'));
        } else {
            $this->io->write(sprintf('Running tasks after deploy'));
        }

        $this->io->increaseIndention(1);

        if (true === $context->isSimulate()) {
            $this->taskRunner->simulate($context->getTarget(), $context->getVersion());
        } else {
            $this->taskRunner->execute($context->getTarget(), $context->getVersion());
        }

        $this->io->decreaseIndention(1);
    }
}
