<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{
    /**
     * Returns a conveyor instance
     *
     * @param  InputInterface                                   $input     The input instance
     * @param  OutputInterface                                  $output    The output instance
     * @param  null|\Symfony\Component\Console\Helper\HelperSet $helperSet The helperSet instance
     * @return \Webcreate\Conveyor\Conveyor
     */
    public function getConveyor(InputInterface $input, OutputInterface $output, $helperSet)
    {
        return $this->getApplication()->getConveyor();
    }
}
