<?php

namespace Webcreate\Conveyor\DependencyInjection;

interface TransporterAwareInterface
{
    public function setTransporter($transporter);
}
