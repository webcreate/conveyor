<?php

class InitCommandTest extends \PHPUnit_Framework_TestCase
{
    public $tmpdir;

    public function setUp()
    {
        $this->tmpdir = sys_get_temp_dir() . '/' . uniqid('conveyor');

        $fs = new \Symfony\Component\Filesystem\Filesystem();
        $fs->mkdir($this->tmpdir);

        chdir($this->tmpdir);
    }

    protected function tearDown()
    {
        $fs = new \Symfony\Component\Filesystem\Filesystem();
        $fs->remove($this->tmpdir);
    }

    public function testExecute()
    {
        $conveyor = new \Webcreate\Conveyor\Conveyor();
        $application = new \Webcreate\Conveyor\Console\Application($conveyor);
        $application->add(new \Webcreate\Conveyor\Command\InitCommand());

        $conveyor->boot(new \Webcreate\Conveyor\IO\NullIO());

        $this->assertFileNotExists($this->tmpdir . '/conveyor.yml');

        $command = $application->find('init');
        $commandTester = new \Symfony\Component\Console\Tester\CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertFileExists($this->tmpdir . '/conveyor.yml');
    }
}
