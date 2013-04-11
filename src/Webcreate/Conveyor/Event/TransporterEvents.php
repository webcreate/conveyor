<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
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
