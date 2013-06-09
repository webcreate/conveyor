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

final class StageEvents
{
    const STAGE_PRE_EXECUTE  = 'stage.preExecute';
    const STAGE_POST_EXECUTE = 'stage.postExecute';
}
