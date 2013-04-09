<?php
/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Util;

class FilePath
{
    public static function join()
    {
        $parts = func_get_args();

        $parts = array_filter($parts, 'trim');

        return implode(DIRECTORY_SEPARATOR, $parts);
    }
}
