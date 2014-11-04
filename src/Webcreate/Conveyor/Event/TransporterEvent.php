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
use Webcreate\Conveyor\Transporter\AbstractTransporter;

class TransporterEvent extends Event
{
    /**
     * @var AbstractTransporter
     */
    protected $transporter;

    /**
     * @var mixed|null
     */
    protected $data;

    /**
     * Constructor.
     *
     * @param AbstractTransporter $transporter
     * @param mixed               $data        depending on the event some data can be given
     */
    public function __construct(AbstractTransporter $transporter, $data = null)
    {
        $this->transporter = $transporter;
        $this->data        = $data;
    }

    /**
     * @return AbstractTransporter
     */
    public function getTransporter()
    {
        return $this->transporter;
    }

    /**
     * @return mixed|null
     */
    public function getData()
    {
        return $this->data;
    }
}
