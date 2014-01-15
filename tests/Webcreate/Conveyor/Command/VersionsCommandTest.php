<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Webcreate\Vcs\Common\Reference;
use Webcreate\Vcs\Svn\Parser\CliParser;
use Webcreate\Util\Cli;
use Webcreate\Vcs\Common\Adapter\CliAdapter;
use Webcreate\Vcs\Svn;
use Webcreate\Vcs\Svn\Svnadmin;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Tester\CommandTester;
use Webcreate\Conveyor\Command\VersionsCommand;
use Webcreate\Conveyor\Console\Application;

/**
 * @group integration
 */
class VersionsCommandTest extends PHPUnit_Framework_TestCase
{
    public $tmpdir;
    public $projectdir;
    public $reposdir;
    public $reposurl;
    public $filesystem;

    public function setUp()
    {
        $this->tmpdir = sys_get_temp_dir() . '/' . uniqid('conveyor');
        $this->projectdir = $this->tmpdir . '/project';
        $this->reposdir = $this->tmpdir . '/repos';
        $this->reposurl = 'file:///' . $this->reposdir;

        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tmpdir);
        $this->filesystem->mkdir($this->projectdir);

        $svnadminbin = getenv('SVNADMIN_BIN') ? getenv('SVNADMIN_BIN') : '/usr/local/bin/svnadmin';
        $svnbin = getenv('SVN_BIN') ? getenv('SVN_BIN') : '/usr/local/bin/svn';

        if (!file_exists($svnadminbin)) {
            $this->markTestSkipped(sprintf('%s not found', $svnadminbin));
        }

        if (!file_exists($svnbin)) {
            $this->markTestSkipped(sprintf('%s not found', $svnbin));
        }

        $svnadmin = new Svnadmin($this->tmpdir, $svnadminbin);
        $svnadmin->create(basename($this->reposdir));

        $svn = new Svn($this->reposurl, new CliAdapter($svnbin, new Cli(), new CliParser()));
        $svn->import(__DIR__ . '/../Test/Fixtures/skeleton/svn/trunk', '/', 'imported skeleton');

        $svn->setHead(new Reference('2.1', Reference::TAG));
        $svn->import(__DIR__ . '/../Test/Fixtures/skeleton/svn/tags/2.1', '/', 'imported skeleton');

        $svn->setHead(new Reference('feature1', Reference::BRANCH));
        $svn->import(__DIR__ . '/../Test/Fixtures/skeleton/svn/branches/feature1', '/', 'imported skeleton');

        $content = file_get_contents(__DIR__ . '/../Test/Fixtures/conveyor.yml.twig');
        $content = str_replace('{{ repository.url }}', $this->reposurl, $content);
        file_put_contents($this->projectdir . '/conveyor.yml', $content);

        chdir($this->projectdir);
    }

    public function testExecute()
    {
        $conveyor = new \Webcreate\Conveyor\Conveyor();
        $application = new Application($conveyor);
        $application->add(new VersionsCommand());

        $conveyor->boot(new \Webcreate\Conveyor\IO\NullIO());

        $command = $application->find('versions');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $output = $commandTester->getDisplay();

        $this->assertContains('dev-feature1', $output);
        $this->assertContains('dev-trunk', $output);
        $this->assertContains('2.1', $output);
    }

    public function tearDown()
    {
        $this->filesystem->remove($this->tmpdir);
    }
}
