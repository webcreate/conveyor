<?php

namespace Webcreate\Conveyor\IO;

use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The Input/Output helper.
 *
 * @author François Pluchino <francois.pluchino@opendisplay.com>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 */
class ConsoleIO implements IOInterface
{
    protected $input;
    protected $output;
    protected $helperSet;
    protected $authorizations = array();
    protected $lastMessage;
    protected $lastMessageNewline = true;
    protected $indention = 0;
    protected $prefix;

    /**
     * Constructor.
     *
     * @param InputInterface  $input     The input instance
     * @param OutputInterface $output    The output instance
     * @param HelperSet       $helperSet The helperSet instance
     */
    public function __construct(InputInterface $input, OutputInterface $output, HelperSet $helperSet)
    {
        $this->input = $input;
        $this->output = $output;
        $this->helperSet = $helperSet;
    }

    /**
     * {@inheritDoc}
     */
    public function isInteractive()
    {
        return $this->input->isInteractive();
    }

    /**
     * {@inheritDoc}
     */
    public function isDecorated()
    {
        return $this->output->isDecorated();
    }

    /**
     * {@inheritDoc}
     */
    public function isVerbose()
    {
        return (bool) $this->input->getOption('verbose');
    }

    /**
     * Set verbose option
     *
     * @param bool $option
     */
    public function setVerbose($option)
    {
        $this->input->setOption('verbose', (bool) $option);
    }

    /**
     * @param string|array $messages
     * @return string|array
     */
    protected function applyIndention($messages)
    {
        if (is_array($messages)) {
            foreach ($messages as &$message) {
                $message = str_repeat(' ', $this->indention) . $message;
            }
        } else {
            $messages = str_repeat(' ', $this->indention) . $messages;
        }

        return $messages;
    }

    /**
     * @param string|array $messages
     * @return string|array
     */
    protected function applyPrefix($messages)
    {
        if (!$this->prefix || (!$this->lastMessageNewline && trim($messages) == '')) {
            return $messages;
        }

        if (is_array($messages)) {
            foreach ($messages as &$message) {
                $message = $this->prefix . $message;
            }
        } else {
            $messages = $this->prefix . $messages;
        }

        return $messages;
    }

    /**
     * {@inheritDoc}
     */
    public function write($messages, $newline = true)
    {
        $messages = $this->applyIndention($messages);
        $messages = $this->applyPrefix($messages);

        $this->_write($messages, $newline);
    }

    /**
     * {@inheritDoc}
     */
    protected function _write($messages, $newline = true)
    {
        $this->output->write($messages, $newline);
        $this->lastMessage = join($newline ? PHP_EOL : '', (array) $messages);
        $this->lastMessageNewline = $newline;
    }

    /**
     * {@inheritDoc}
     */
    public function overwrite($messages, $newline = true, $size = null)
    {
        $messages = $this->applyIndention($messages);
        $messages = $this->applyPrefix($messages);

        // messages can be an array, let's convert it to string anyway
        $messages = join($newline ? PHP_EOL : '', (array) $messages);

        // since overwrite is supposed to overwrite last message...
        if (!isset($size)) {
            // removing possible formatting of lastMessage with strip_tags
            $size = strlen(strip_tags($this->lastMessage));
            $size+= $this->indention;
            if ($this->prefix) {
                $size += strlen($this->prefix);
            }
        }
        // ...let's fill its length with backspaces
        $this->_write(str_repeat("\x08", $size), false);

        // write the new message
        $this->_write($messages, false);

        $fill = $size - strlen(strip_tags($messages));
        if ($fill > 0) {
            // whitespace whatever has left
            $this->_write(str_repeat(' ', $fill), false);
            // move the cursor back
            $this->_write(str_repeat("\x08", $fill), false);
        }

        if ($newline) {
            $this->_write('');
        }
        $this->lastMessage = $messages;
        $this->lastMessageNewline = $newline;
    }

    /**
     * {@inheritDoc}
     */
    public function select($question, $choices, $default = null, $attempts = false, $errorMessage = 'Value "%s" is invalid')
    {
        if ($this->isInteractive()) {
            return $this->helperSet->get('dialog')->select($this->output, $question, $choices, $default, $attempts, $errorMessage);
        } else {
            return $default;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function ask($question, $default = null)
    {
        if ($this->isInteractive()) {
            return $this->helperSet->get('dialog')->ask($this->output, $question, $default);
        } else {
            return $default;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function askConfirmation($question, $default = true)
    {
        if ($this->isInteractive()) {
            return $this->helperSet->get('dialog')->askConfirmation($this->output, $question, $default);
        } else {
            return $default;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function askAndValidate($question, $validator, $attempts = false, $default = null)
    {
        if ($this->isInteractive()) {
            return $this->helperSet->get('dialog')->askAndValidate($this->output, $question, $validator, $attempts, $default);
        } else {
            return $default;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function askAndHideAnswer($question)
    {
        // handle windows
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $exe = __DIR__.'\\hiddeninput.exe';

            // handle code running from a phar
            if ('phar:' === substr(__FILE__, 0, 5)) {
                $tmpExe = sys_get_temp_dir().'/hiddeninput.exe';
                copy($exe, $tmpExe);
                $exe = $tmpExe;
            }

            $this->write($question, false);
            $value = rtrim(shell_exec($exe));
            $this->write('');

            // clean up
            if (isset($tmpExe)) {
                unlink($tmpExe);
            }

            return $value;
        }

        if (file_exists('/usr/bin/env')) {
            // handle other OSs with bash/zsh/ksh/csh if available to hide the answer
            $test = "/usr/bin/env %s -c 'echo OK' 2> /dev/null";
            foreach (array('bash', 'zsh', 'ksh', 'csh') as $sh) {
                if ('OK' === rtrim(shell_exec(sprintf($test, $sh)))) {
                    $shell = $sh;
                    break;
                }
            }
            if (isset($shell)) {
                $this->write($question, false);
                $readCmd = ($shell === 'csh') ? 'set mypassword = $<' : 'read -r mypassword';
                $command = sprintf("/usr/bin/env %s -c 'stty -echo; %s; stty echo; echo \$mypassword'", $shell, $readCmd);
                $value = rtrim(shell_exec($command));
                $this->write('');

                return $value;
            }
        }

        // not able to hide the answer, proceed with normal question handling
        return $this->ask($question);
    }

    /**
     * (non-PHPdoc)
     * @see Webcreate\Conveyor\IO.IOInterface::setIndention()
     */
    public function setIndention($indent)
    {
        $this->indention = $indent;
    }

    /**
     * (non-PHPdoc)
     * @see Webcreate\Conveyor\IO.IOInterface::increaseIndention()
     */
    public function increaseIndention($indent)
    {
        $this->indention += $indent;
    }

    /**
     * (non-PHPdoc)
     * @see Webcreate\Conveyor\IO.IOInterface::decreaseIndention()
     */
    public function decreaseIndention($indent)
    {
        if ($this->indention - $indent < 0) {
            throw new \InvalidArgumentException('Resulting indenting should be greater or equal to 0.');
        }

        $this->indention -= $indent;
    }

    /**
     * (non-PHPdoc)
     * @see Webcreate\Conveyor\IO.IOInterface::getIndention()
     */
    public function getIndention()
    {
        return $this->indention;
    }

    public function renderException($e)
    {
        $indention = $this->getIndention();

        $this->setIndention(0);

        $app = new \Symfony\Component\Console\Application();
        $app->renderException($e, $this->output);

        // restore indention
        $this->setIndention($indention);
    }

    /**
     * @param string $prefix
     * @return mixed
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }
}
