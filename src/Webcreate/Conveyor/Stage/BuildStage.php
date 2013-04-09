<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Stage;

use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\Context;

use Symfony\Component\Filesystem\Filesystem;

class BuildStage extends AbstractStage
{
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
        if (true === $this->validateBuilddir()) {
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
        } else {
            return false;
        }
    }

    protected function validateBuilddir()
    {
        $builddir = $this->builder->getBuildDir();

        if (true === is_dir($builddir)) {
            $anwser = $this->io->askConfirmation(sprintf(
                    'Build directory \'%s\' does already exist, would you like ' .
                    'to overwrite it? (y/N): ',
                    $builddir), false);

            if (true === $anwser) {
                $filesystem = new Filesystem();
                $filesystem->remove($builddir);
            } else {
                return false;
            }
        }

        return true;
    }
}
