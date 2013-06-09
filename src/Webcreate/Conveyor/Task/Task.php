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

use Webcreate\Conveyor\Repository\Version;

/**
 * @todo Instead of returning a ExecuteResult in the task, let each task implement,
 *       a getDerivedFiles() and getRemovedFiles() method. This should remove
 *       the "new ExecuteResult" dependency from the tasks.
 */
abstract class Task
{
    protected $output;
    protected $options;

    abstract public function execute($target, Version $version);

    public function supports($target, Version $version)
    {
        return (
            empty($this->options['targets'])
            || in_array($target, $this->options['targets'])
        );
    }

    public function simulate($target, Version $version)
    {
    }

    /**
     * Sets the options for the task, as configured in the conveyor file.
     *
     * Gets called during the TaskCompilerPass.
     *
     * @param  array $options
     * @return Task
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Sets a specific option
     *
     * @todo validate $name
     *
     * @param $name
     * @param $value
     * @return Task
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;

        return $this;
    }

    public function setOutput(\Closure $callback)
    {
        $this->output = $callback;
    }

    protected function output($output)
    {
        if ($this->output) {
            call_user_func($this->output, $output);
        }
    }
}
