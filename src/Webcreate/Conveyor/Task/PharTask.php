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

use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Finder\Finder;

use Webcreate\Conveyor\Task\Result\ExecuteResult;
use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\Repository\Version;

/**
 * @todo check requirement: phar.readonly = Off in php.ini
 * @author jeroen
 *
 */
class PharTask extends Task
{
    protected $builddir;
    protected $io;
    protected $conveyorFile;
    protected $filename;
    protected $stub;

    public function __construct($builddir, $conveyorFile, IOInterface $io = null)
    {
        $this->io = $io;
        $this->builddir = $builddir;
        $this->conveyorFile = $conveyorFile;
    }

    /**
     * @todo implement an excluded file list, instead of hard-coding the filter for the conveyorFile
     *
     * (non-PHPdoc)
     * @see Webcreate\Conveyor\Task.Task::execute()
     */
    public function execute($target, Version $version = null)
    {
        $filename = $this->options['filename'];
        $conveyorFile = $this->conveyorFile;

        $this->output(sprintf('Creating Phar...'));

        $phar = new \Phar($this->builddir . '/' . $filename, 0, $filename);
        $phar->setSignatureAlgorithm(\Phar::SHA1);

        $phar->startBuffering();

        $finder = new Finder();
        $finder
            ->files()
            ->ignoreVCS(true)
            ->in($this->builddir)
        ;

        foreach ($finder as $file) {
            $this->addFile($phar, $file, false);
        }

        // Stubs
        $phar->setStub($this->getStub());

        $phar->stopBuffering();

        unset($phar);

        return new ExecuteResult(array($filename));
    }

    private function addFile($phar, \SplFileInfo $file)
    {
        $path = $file->getRelativePathname();

        $content = file_get_contents($file);

        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);

        $phar->addFromString($path, $content);
    }

    private function getStub()
    {
        $stub = <<<'EOF'
#!/usr/bin/env php
<?php

Phar::mapPhar('@@filename@@');

require 'phar://@@filename@@/@@stub@@';

__HALT_COMPILER();

EOF;

        $stub = str_replace('@@filename@@', $this->options['filename'], $stub);
        $stub = str_replace('@@stub@@', $this->options['stub'], $stub);

        return $stub;
    }
}
