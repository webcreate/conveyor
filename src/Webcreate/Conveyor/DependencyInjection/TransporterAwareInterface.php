<?php

namespace Webcreate\Conveyor\DependencyInjection;

interface TransporterAwareInterface
{
    /**
     * @return void
     */
    public function setTransporter($transporter);
}
