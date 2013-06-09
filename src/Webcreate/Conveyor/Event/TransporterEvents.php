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

final class TransporterEvents
{
    const TRANSPORTER_CONNECT     = 'transporter.connect';
    const TRANSPORTER_PUT         = 'transporter.put';
    const TRANSPORTER_PUT_CONTENT = 'transporter.put_content';
    const TRANSPORTER_GET         = 'transporter.get';
    const TRANSPORTER_MKDIR       = 'transporter.mkdir';
    const TRANSPORTER_SYMLINK     = 'transporter.symlink';
    const TRANSPORTER_COPY        = 'transporter.copy';
    const TRANSPORTER_REMOVE      = 'transporter.remove';
}
