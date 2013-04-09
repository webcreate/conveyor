<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Stage;

use Webcreate\Conveyor\Context;

abstract class AbstractStage
{
    abstract public function supports(Context $context);
    abstract public function execute(Context $context);
}
