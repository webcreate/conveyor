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

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\Repository\Version;
use Webcreate\Conveyor\Task\Result\ExecuteResult;

/**
 * @todo check requirement: phar.readonly = Off in php.ini
 * @author jeroen
 *
 */
class PharTask extends Task
{
    /**
     * @var string
     */
    protected $buildDir;

    /**
     * @var \Webcreate\Conveyor\IO\IOInterface
     */
    protected $io;
    protected $conveyorFile;
    protected $filename;
    protected $stub;

    /**
     * Constructor.
     *
     * @param string $buildDir
     * @param $conveyorFile
     * @param IOInterface $io
     */
    public function __construct($buildDir, $conveyorFile, IOInterface $io = null)
    {
        $this->io           = $io;
        $this->buildDir     = $buildDir;
        $this->conveyorFile = $conveyorFile;
    }

    /**
     * @todo implement an excluded file list, instead of hard-coding the filter for the conveyorFile
     *
     * @inheritdoc
     */
    public function execute($target, Version $version = null)
    {
        $filename = $this->options['filename'];
        $conveyorFile = $this->conveyorFile;

        $this->output(sprintf('Creating Phar...'));

        $phar = new \Phar($this->buildDir . '/' . $filename, 0, $filename);
        $phar->setSignatureAlgorithm(\Phar::SHA1);

        $phar->startBuffering();

        $finder = new Finder();
        $finder
            ->files()
            ->ignoreVCS(true)
            ->in($this->buildDir)
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

    /**
     * @param \Phar $phar
     * @param SplFileInfo $file
     */
    private function addFile(\Phar $phar, SplFileInfo $file)
    {
        $path = $file->getRelativePathname();

        $content = file_get_contents($file);

        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);

        $phar->addFromString($path, $content);
    }

    /**
     * @return string
     */
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
