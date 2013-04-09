<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Command;

use Webcreate\Conveyor\IO\ConsoleIO;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\Command;

abstract class AbstractCommand extends Command
{
    /**
     * Returns a conveyor instance
     *
     * @param  InputInterface               $input     The input instance
     * @param  OutputInterface              $output    The output instance
     * @param  HelperSet                    $helperSet The helperSet instance
     * @return \Webcreate\Conveyor\Conveyor
     */
    public function getConveyor(InputInterface $input, OutputInterface $output, $helperSet)
    {
        return $this->getApplication()->getConveyor();
    }
}
