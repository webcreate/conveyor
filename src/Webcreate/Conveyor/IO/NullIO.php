<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\IO;

use Webcreate\Conveyor\IO\IOInterface;

class NullIO implements IOInterface
{
    public function isInteractive()
    {
        return false;
    }

    public function isVerbose()
    {
        return false;
    }

    public function isDecorated()
    {
        return false;
    }

    public function write($messages, $newline = true)
    {
    }

    public function overwrite($messages, $newline = true, $size = 80)
    {
    }

    public function select($question, $choices, $default = null, $attempts = false, $errorMessage = 'Value "%s" is invalid')
    {
        return $default;
    }

    public function ask($question, $default = null)
    {
        return $default;
    }

    public function askConfirmation($question, $default = true)
    {
        return $default;
    }

    public function askAndValidate($question, $validator, $attempts = false,
            $default = null)
    {
        return $default;
    }

    public function askAndHideAnswer($question)
    {
        return null;
    }

    public function setIndention($indent)
    {
    }

    public function increaseIndention($indent)
    {
    }

    public function decreaseIndention($indent)
    {
    }

    public function getIndention()
    {
        return 0;
    }

    /**
     * Renders an exception
     *
     * @param  Exception $e
     * @return mixed
     */
    public function renderException($e)
    {
    }
}
