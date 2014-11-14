<?php

namespace Webcreate\Conveyor\IO;

/**
 * The Input/Output helper interface.
 *
 * @author François Pluchino <francois.pluchino@opendisplay.com>
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 */
interface IOInterface
{
    /**
     * Is this input means interactive?
     *
     * @return bool
     */
    public function isInteractive();

    /**
     * Is this input verbose?
     *
     * @return bool
     */
    public function isVerbose();

    /**
     * Is this output decorated?
     *
     * @return bool
     */
    public function isDecorated();

    /**
     * Writes a message to the output.
     *
     * @param string $messages The message as an array of lines or a single string
     * @param bool         $newline  Whether to add a newline or not
     * @return void
     */
    public function write($messages, $newline = true);

    /**
     * Overwrites a previous message to the output.
     *
     * @param string $messages The message as an array of lines or a single string
     * @param bool         $newline  Whether to add a newline or not
     * @param integer      $size     The size of line
     * @return void
     */
    public function overwrite($messages, $newline = true, $size = 80);

    /**
     * Asks the user to select a value.
     *
     * @param string    $question     The question to ask
     * @param array           $choices      List of choices to pick from
     * @param Boolean         $default      The default answer if the user enters nothing
     * @param boolean $attempts     Max number of times to ask before giving up (false by default, which means infinite)
     * @param string          $errorMessage Message which will be shown if invalid value from choice list would be picked
     *
     * @return integer|string The selected value (the key of the choices array)
     *
     * @throws \InvalidArgumentException
     */
    public function select($question, $choices, $default = null, $attempts = false, $errorMessage = 'Value "%s" is invalid');

    /**
     * Asks a question to the user.
     *
     * @param string|array $question The question to ask
     * @param string       $default  The default answer if none is given by the user
     *
     * @return string The user answer
     *
     * @throws \RuntimeException If there is no data to read in the input stream
     */
    public function ask($question, $default = null);

    /**
     * Asks a confirmation to the user.
     *
     * The question will be asked until the user answers by nothing, yes, or no.
     *
     * @param string $question The question to ask
     * @param bool         $default  The default answer if the user enters nothing
     *
     * @return bool true if the user has confirmed, false otherwise
     */
    public function askConfirmation($question, $default = true);

    /**
     * Asks for a value and validates the response.
     *
     * The validator receives the data to validate. It must return the
     * validated data when the data is valid and throw an exception
     * otherwise.
     *
     * @param string|array $question  The question to ask
     * @param callback     $validator A PHP callback
     * @param integer      $attempts  Max number of times to ask before giving up (false by default, which means infinite)
     * @param string       $default   The default answer if none is given by the user
     *
     * @return mixed
     *
     * @throws \Exception When any of the validators return an error
     */
    public function askAndValidate($question, $validator, $attempts = false, $default = null);

    /**
     * Asks a question to the user and hide the answer.
     *
     * @param string $question The question to ask
     *
     * @return string The answer
     */
    public function askAndHideAnswer($question);

    /**
     * Set indention
     *
     * @param int $indent
     * @return void
     */
    public function setIndention($indent);

    /**
     * Increase indention
     *
     * @param int $indent
     * @return void
     */
    public function increaseIndention($indent);

    /**
     * Decrease indention
     *
     * @param int $indent
     * @return void
     */
    public function decreaseIndention($indent);

    /**
     * Return indention
     *
     * @return string
     */
    public function getIndention();

    /**
     * Renders an exception
     *
     * @param  \Exception $e
     * @return mixed
     */
    public function renderException($e);

    /**
     * Sets prefix
     *
     * @param string $prefix
     * @return mixed
     */
    public function setPrefix($prefix);
}
