<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Event;

final class TaskRunnerEvents
{
    const TASKRUNNER_PRE_EXECUTE_TASK   = 'taskrunner.preTask';
    const TASKRUNNER_POST_EXECUTE_TASK  = 'taskrunner.postTask';
}
