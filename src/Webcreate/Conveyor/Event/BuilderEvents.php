<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Event;

final class BuilderEvents
{
    const BUILDER_PRE_BUILD  = 'builder.preBuild';
    const BUILDER_POST_BUILD = 'builder.postBuild';
    const BUILDER_PRE_TASK   = 'builder.preTask';
    const BUILDER_POST_TASK  = 'builder.postTask';
}
