<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Stage;

use Symfony\Component\Filesystem\Filesystem;
use Webcreate\Conveyor\Builder\Builder;
use Webcreate\Conveyor\Context;
use Webcreate\Conveyor\IO\IOInterface;

class BuildStage extends AbstractStage
{
    /**
     * @param Builder     $builder
     * @param IOInterface $io
     */
    public function __construct($builder, IOInterface $io)
    {
        $this->builder = $builder;
        $this->io = $io;
    }

    public function supports(Context $context)
    {
        return true;
    }

    public function execute(Context $context)
    {
        if (true !== $this->validateBuildDir()) {
            return false;
        }

        $this->io->write('');
        $this->io->write(
            sprintf('Building <info>%s</info> (<comment>%s</comment>) to <info>%s</info>',
                $context->getVersion()->getName(),
                $context->getTarget(),
                $context->getBuilddir()
            )
        );

        $this->io->increaseIndention(1);

        $this->builder->setContext($context);
        $this->builder->build($context->getTarget(), $context->getVersion());

        $this->io->decreaseIndention(1);

        return true;
    }

    protected function validateBuildDir()
    {
        $buildDir = $this->builder->getBuildDir();

        if (true === is_dir($buildDir)) {
            $answer = $this->io->askConfirmation(sprintf(
                    'Build directory \'%s\' does already exist, would you like ' .
                    'to overwrite it? (y/N): ',
                    $buildDir), false);

            if (true === $answer) {
                $filesystem = new Filesystem();
                $filesystem->remove($buildDir);
            } else {
                return false;
            }
        }

        return true;
    }
}
