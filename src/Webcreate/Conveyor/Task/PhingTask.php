<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Task;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\Repository\Version;
use Webcreate\Conveyor\Task\Task;
use Webcreate\Util\Cli;

/**
 * Phing task
 *
 * Calls Phing via the commandline.
 *
 * Available options:
 *     - buildfile    name of the buildfile to use
 *     - target       array containing phing targets to execute
 *
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 *
 */
class PhingTask extends Task
{
    protected $cwd;
    protected $cli;
    protected $io;

    /**
     * Constructor.
     *
     * @param string      $cwd current working directory, this will probably be the builddir
     * @param Cli         $cli
     * @param IOInterface $io
     */
    public function __construct($cwd, Cli $cli, IOInterface $io = null)
    {
        $this->cwd = $cwd;
        $this->cli = $cli;
        $this->io = $io;
    }

    public function execute($target, Version $version = null)
    {
        $arguments = array();

        if (isset($this->options['buildfile'])
            && null !== $buildfile = $this->options['buildfile'])
        {
            $arguments['-f'] = $buildfile;
        }

        $arguments = array_merge($arguments, $this->options['target']);

        $commandline = $this->cli->prepare('phing', $arguments);

        $this->output(sprintf('Executing: <comment>%s</comment>', $commandline));
        if ($this->cli->execute($commandline, null, $this->cwd) <> 0) {
            throw new ProcessFailedException($this->cli->getProcess());
        } elseif ($message = $this->cli->getErrorOutput()) {
            throw new \RuntimeException($message);
        }
    }
}
