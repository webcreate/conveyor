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
