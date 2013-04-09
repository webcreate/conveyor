<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Event;

use Symfony\Component\EventDispatcher\Event;

class TransporterEvent extends Event
{
    protected $transporter;
    protected $data;

    /**
     * Constructor.
     *
     * @param object $transporter
     * @param mixed  $data        depending on the event some data can be given
     */
    public function __construct($transporter, $data = null)
    {
        $this->transporter = $transporter;
        $this->data        = $data;
    }

    public function getTransporter()
    {
        return $this->transporter;
    }

    public function getData()
    {
        return $this->data;
    }
}
