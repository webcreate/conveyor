<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Util;

class ArrayUtil
{
    /**
     * @param $array
     * @return array
     */
    public function flatten($array)
    {
        return $this->_flatten($array, '');
    }

    /**
     * @param array $array
     * @param string $opath
     * @return array
     */
    protected function _flatten(array $array, $opath)
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
