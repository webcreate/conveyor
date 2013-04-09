<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Event;

final class BuilderEvents
{
    const BUILDER_PRE_BUILD  = 'builder.preBuild';
    const BUILDER_POST_BUILD = 'builder.postBuild';
    const BUILDER_PRE_TASK   = 'builder.preTask';
    const BUILDER_POST_TASK  = 'builder.postTask';
}
