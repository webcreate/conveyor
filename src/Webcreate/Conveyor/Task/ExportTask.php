<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
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
