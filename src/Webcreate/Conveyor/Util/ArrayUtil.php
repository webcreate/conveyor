<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Util;

class ArrayUtil
{
    public function flatten($array)
    {
        return $this->_flatten($array, '');
    }

    protected function _flatten($array, $opath)
    {
        $flatten = array();

        foreach ($array as $key => $value) {
            $path = $opath . ($opath ? '.' : '') . $key;

            if (is_array($value)) {
                $_flatten = $this->_flatten($value, $path);
                foreach ($_flatten as $_path => $_value) {
                    $flatten[$_path] = $_value;
                }
            } else {
                $flatten[$path] = $value;
            }
        }

        return $flatten;
    }
}
