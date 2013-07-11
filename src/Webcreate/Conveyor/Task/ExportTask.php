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

use Webcreate\Conveyor\Task\Result\ExecuteResult;
use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\Repository\Version;
use Webcreate\Conveyor\Repository\Repository;

class ExportTask extends Task
{
    protected $io;
    protected $repository;

    public function __construct($dest, Repository $repository, IOInterface $io = null)
    {
        $this->repository = $repository;
        $this->io = $io;
        $this->dest = $dest;
    }

    /**
     * @todo better progress (percentage) updating
     *
     * @param $target
     * @param Version $version
     * @param array $options
     * @return ExecuteResult
     */
    public function execute($target, Version $version, array $options = array())
    {
        $this->output(sprintf('Exporting: <comment>0%%</comment>'));

        $this->repository->export($version, $this->dest);

        $this->output(sprintf('Exporting: <comment>100%%</comment>'));

        return new ExecuteResult(
            array('*')
        );
    }
}
