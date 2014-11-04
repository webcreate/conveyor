<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webcreate\Conveyor\Conveyor;
use Webcreate\Conveyor\IO\ConsoleIO;

class Application extends BaseApplication
{
    protected $conveyor;
    protected $originalAutoExit;

    public function __construct(Conveyor $conveyor)
    {
        $this->conveyor = $conveyor;

        if (function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get')) {
            date_default_timezone_set(@date_default_timezone_get());
        }

        parent::__construct();
    }

    public function getConveyor()
    {
        return $this->conveyor;
    }

    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();
        $commands[] = new \Webcreate\Conveyor\Command\VersionsCommand();
        $commands[] = new \Webcreate\Conveyor\Command\BuildCommand();
        $commands[] = new \Webcreate\Conveyor\Command\SimulateCommand();
        $commands[] = new \Webcreate\Conveyor\Command\DeployCommand();
        $commands[] = new \Webcreate\Conveyor\Command\DiffCommand();
        $commands[] = new \Webcreate\Conveyor\Command\InitCommand();
        $commands[] = new \Webcreate\Conveyor\Command\ValidateCommand();
        $commands[] = new \Webcreate\Conveyor\Command\StatusCommand();

        if ('phar:' === substr(__FILE__, 0, 5)) {
            $commands[] = new \Webcreate\Conveyor\Command\UpdateCommand();
        }


        return $commands;
    }

    protected function getDefaultInputDefinition()
    {
        $inputDefinition = parent::getDefaultInputDefinition();
        $inputDefinition->addOption(
            new InputOption('--configuration', '-c', InputOption::VALUE_REQUIRED, 'Configuration file override')
        );

        return $inputDefinition;
    }

    public function getName()
    {
        return 'Conveyor';
    }

    public function getVersion()
    {
        $versionFilename = 'VERSION';

        if ('phar:' === substr(__FILE__, 0, 5)) {
            $versionFile = 'phar://conveyor.phar/' . $versionFilename;
        } else {
            $versionFile = __DIR__ . '/../../../../' . $versionFilename;
        }

        return trim(file_get_contents($versionFile));
    }

    /**
     * Runs the current application.
     *
     * @param InputInterface  $input  An Input instance
     * @param OutputInterface $output An Output instance
     *
     * @return integer 0 if everything went fine, or an error code
     *
     * @throws \Exception When doRun returns Exception
     *
     * @api
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        $this->setCatchExceptions(false);

        if (null === $input) {
            $input = new ArgvInput();
        }

        if (null === $output) {
            $output = new ConsoleOutput();
        }

        try {
            $statusCode = parent::run($input, $output);
        } catch (\Exception $e) {
            $container = $this->getConveyor()->getContainer();

            if (null !== $container) {
                /** @var $logger \Monolog\Logger */
                $logger = $this->getConveyor()->getContainer()->get('logger');

                $message = sprintf(
                    '%s: %s (uncaught exception) at %s line %s while running console command `%s`',
                    get_class($e),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    $this->getCommandName($input)
                );
                $logger->addCritical($message);
            }

            if ($output instanceof ConsoleOutputInterface) {
                $this->renderException($e, $output->getErrorOutput());
            } else {
                $this->renderException($e, $output);
            }
            $statusCode = $e->getCode();

            $statusCode = is_numeric($statusCode) && $statusCode ? $statusCode : 1;
        }

        if ($this->originalAutoExit) {
            if ($statusCode > 255) {
                $statusCode = 255;
            }
            // @codeCoverageIgnoreStart
            exit($statusCode);
            // @codeCoverageIgnoreEnd
        }

        return $statusCode;
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $io = new ConsoleIO($input, $output, $this->getHelperSet());
        $conveyor = $this->getConveyor();

        // retrieve the config file option really early so we can still make changes before the di container
        // gets compiled
        $configFile = $input->getParameterOption(array('--configuration', '-c'));
        if ($configFile) {
            $conveyor->setConfigFile($configFile);
        }

        $conveyor->boot($io);

        parent::doRun($input, $output);
    }

    public function setAutoExit($bool)
    {
        // parent property is private, so we need to intercept it in a setter
        $this->originalAutoExit = (bool) $bool;
        parent::setAutoExit($bool);
    }
}
