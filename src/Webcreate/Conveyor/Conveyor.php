<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;
use Webcreate\Conveyor\DependencyInjection\Compiler\ParameterCompilerPass;
use Webcreate\Conveyor\DependencyInjection\Compiler\StrategyCompilerPass;
use Webcreate\Conveyor\DependencyInjection\Compiler\TaskCompilerPass;
use Webcreate\Conveyor\DependencyInjection\Compiler\TransporterCompilerPass;
use Webcreate\Conveyor\DependencyInjection\TransporterAwareInterface;
use Webcreate\Conveyor\Event\StageEvent;
use Webcreate\Conveyor\Event\StageEvents;
use Webcreate\Conveyor\Exception\EmptyChangesetException;
use Webcreate\Conveyor\Factory\StrategyFactory;
use Webcreate\Conveyor\IO\IOInterface;
use Webcreate\Conveyor\IO\NullIO;
use Webcreate\Conveyor\Repository\Repository;
use Webcreate\Conveyor\Repository\Version;
use Webcreate\Conveyor\Stage\Manager\StageManager;
use Webcreate\Conveyor\Strategy\StrategyInterface;
use Webcreate\Conveyor\Task\TaskRunner;
use Webcreate\Conveyor\Transporter\AbstractTransporter;
use Webcreate\Conveyor\Transporter\TransactionalTransporterInterface;
use Webcreate\Conveyor\Util\FilePath;

class Conveyor
{
    /**
     * @var Container
     */
    protected $container;
    protected $booted;
    protected $strategy;
    protected $configFile;

    public function boot(IOInterface $io)
    {
        if (true === $this->booted) {
            return;
        }

        $this->container = $this->buildContainer($io);

        $this->booted = true;
    }

    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return \Webcreate\Conveyor\Config\YamlConfig
     */
    public function getConfig()
    {
        return $this->container->get('config');
    }

    /**
     * @return \Webcreate\Conveyor\Repository\Repository
     */
    public function getRepository()
    {
        return $this->container->get('repository');
    }

    /**
     * @return \Webcreate\Conveyor\Builder\Builder
     */
    public function getBuilder()
    {
        return $this->container->get('builder');
    }

    /**
     * @param  string              $target name of the target to get the transporter for
     * @return AbstractTransporter
     */
    public function getTransporter($target)
    {
        $factory = $this->container->get('transporter.factory');

        $config = $this->getConfig()->getConfig();

        $options = $config['targets'][$target]['transport'];

        return $factory->get($options['type'], $options);
    }

    /**
     * @return \Webcreate\Conveyor\IO\IOInterface
     */
    public function getIO()
    {
        return $this->container->get('io');
    }

    /**
     * @param  null|AbstractTransporter $transporter
     * @return StrategyInterface
     */
    public function getStrategy($transporter = null)
    {
        /** @var StrategyFactory $factory */
        $factory = $this->container->get('strategy.factory');
        $config = $this->getConfig()->getConfig();
        $dispatcher = $this->container->get('dispatcher');

        if (null === $this->strategy) {
            $options = $config['deploy']['strategy'];

            $strategy = $factory->get($options['type'], $options);

            if ($strategy instanceof TransporterAwareInterface) {
                $strategy->setTransporter($transporter);
            }

            if ($strategy instanceof EventSubscriberInterface) {
                $dispatcher->addSubscriber($strategy);
            }

            $this->strategy = $strategy;
        }

        return $this->strategy;
    }

    /**
     * Create a conveyor file
     *
     * @param  string  $filename
     * @return boolean
     */
    public function init($filename = null)
    {
        if (null === $filename) {
            $filename = $this->container->getParameter('conveyor.configfile');
        }

        if (file_exists($filename)) {
            $this->getIO()->write(sprintf('<error>%s already exists.</error>', $filename));

            return false;
        } else {
            $this->getIO()->write(sprintf('Writing <info>%s</info>', $filename));
            file_put_contents($filename, file_get_contents(__DIR__ . '/Resources/conveyor.yml'));
        }

        return true;
    }

    /**
     * Validate conveyor file
     *
     * @throws \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @return boolean
     */
    public function validate()
    {
        $config = $this->getConfig();
        $config->getConfig();

        return true;
    }

    /**
     * Returns available versions
     *
     * @return Version[]
     */
    public function getVersions()
    {
        return $this->getRepository()->getVersions();
    }

    /**
     * Retrieve the status for each target
     *
     * @param  null|string $target when given only get the status for a single target
     * @return array
     */
    public function status($target = null)
    {
        $config         = $this->getConfig()->getConfig();
        $targets        = (null !== $target) ? array($target) : array_keys($config['targets']);
        $repository     = $this->getRepository();
        $remoteInfoFile = $this->container->getParameter('conveyor.remoteinfofile');

        $retval = array();

        foreach ($targets as $target) {
            $this->assertTargetExists($target, $config);
        }

        foreach ($targets as $target) {
            $transporter = $this->getTransporter($target);
            $strategy    = $this->getStrategy($transporter);

            $context = new Context();
            $context
                ->setTarget($target)
                ->setStrategy($strategy)
            ;

            try {
                $versionFile = FilePath::join(
                    $transporter->getPath(),
                    $strategy->getCurrentReleasePath(),
                    $remoteInfoFile
                );

                $isDeployed = $transporter->exists($versionFile);

                if ($isDeployed) {
                    $manager = new StageManager($context, $this->container->get('dispatcher'));
                    $manager->addStage('get.remote.version', new Stage\RetrieveRemoteVersionInfoStage($transporter, $repository, new NullIO(), $remoteInfoFile, array('getRemoteVersion')));
                    $manager->execute();

                    // @todo don't compare to master branch, compare to the remote version's ancestor
                    $localVersion = $repository->getVersion(sprintf('dev-%s', $repository->getMasterBranch()));
                    $remoteVersion = $context->getRemoteVersion();

                    $compare = $repository->versionCompare($localVersion, $remoteVersion);

                    $changelog = array();

                    if (1 === $compare) {
                        $changelog = $repository->changelog($remoteVersion, $localVersion);
                    } elseif (-1 === $compare) {
                        $changelog = $repository->changelog($localVersion, $remoteVersion);
                    }

                    $retval[$target] = array(
                        'remoteVersion' => $context->getRemoteVersion(),
                        'localVersion'  => $localVersion,
                        'changelog'     => $changelog,
                        'compare'       => $compare,
                    );
                } else {
                    $retval[$target] = false;
                }
            } catch (\Exception $e) {
                $retval[$target] = array(
                    'error' => $e,
                );
            }
        }

        return $retval;
    }

    /**
     * Builds version for target
     *
     * @param string         $target
     * @param Version|string $version
     */
    public function build($target, $version)
    {
        if (is_string($version)) {
            $version = $this->getRepository()->getVersion($version);
        }

        $this->assertTargetExists($target, $this->getConfig()->getConfig());

        $this->setConfigParametersForTarget($target);
        $this->setConfigParametersForVcs($this->getRepository(), $version);

        $builder = $this->getBuilder();
        $io      = $this->getIO();

        $context = new Context();
        $context
            ->setBuilddir($builder->getBuildDir())
            ->setVersion($version)
            ->setTarget($target)
        ;

        $manager = new StageManager($context, $this->container->get('dispatcher'));
        $manager
            ->addStage('build', new Stage\BuildStage($builder, $io))
            ->execute()
        ;
    }

    /**
     * Deploys version to target
     *
     * @param string $target
     * @param string $version
     * @param array  $options
     */
    public function deploy($target, $version, array $options = array())
    {
        $this->assertTargetOrGroupExists($target, $this->getConfig()->getConfig());

        if ($this->isTargetGroup($target)) {
            $targets = $this->getTargetsForGroup($target);
        } else {
            $targets = array($target);
        }

        foreach ($targets as $target) {
            $this->getIO()->setPrefix(sprintf('[%s] ', $target));

            $this->doDeploy($target, $version, $options);

            $this->getIO()->setPrefix(null);
        }
    }

    /**
     * Simulates a deploy
     *
     * @param string $target
     * @param string $version
     * @param array  $options
     */
    public function simulate($target, $version, array $options = array())
    {
        $this->assertTargetOrGroupExists($target, $this->getConfig()->getConfig());

        if ($this->isTargetGroup($target)) {
            $targets = $this->getTargetsForGroup($target);
        } else {
            $targets = array($target);
        }

        foreach ($targets as $target) {
            $this->getIO()->setPrefix(sprintf('[%s] ', $target));

            $this->doSimulate($target, $version, $options);

            $this->getIO()->setPrefix(null);
        }
    }

    public function undeploy($target, array $options = array())
    {
        $this->assertTargetExists($target, $this->getConfig()->getConfig());

        $this->setConfigParametersForTarget($target);
        $this->setConfigParametersForVcs($this->getRepository(), new Version());

        /** @var \Webcreate\Conveyor\Task\TaskRunner $trUndeploy */
        $transporter = $this->getTransporter($target);
        $io          = $this->getIO();
        $trUndeploy  = $this->container->get('undeploy.taskrunner');
        $strategy    = $this->getStrategy($transporter);

        $trUndeploy->setTransporter($transporter);

        $context = new Context();
        $context
            ->setTarget($target)
            ->setStrategy($strategy)
            ->setVersion(new Version())
        ;

        $manager = new StageManager($context, $this->container->get('dispatcher'));
        $manager
            ->addStage('undeploy', new Stage\UndeployStage($trUndeploy, $transporter, $io))
            ->execute()
        ;
    }

    /**
     * Performs a diff with the target
     *
     * @param string $target
     * @param string $version
     */
    public function diff($target, $version)
    {
        $version = $this->getRepository()->getVersion($version);

        $this->assertTargetExists($target, $this->getConfig()->getConfig());

        $transporter    = $this->getTransporter($target);
        $repository     = $this->getRepository();
        $io             = $this->getIO();
        $remoteInfoFile = $this->container->getParameter('conveyor.remoteinfofile');
        $strategy       = $this->getStrategy($transporter);

        $context = new Context();
        $context
            ->setVersion($version)
            ->setTarget($target)
            ->setStrategy($strategy)
        ;

        $manager = new StageManager($context, $this->container->get('dispatcher'));
        $manager
            ->addStage('get.remote.version', new Stage\RetrieveRemoteVersionInfoStage($transporter, $repository, $io, $remoteInfoFile, array('getRemoteVersion')))
            ->addStage('diff',               new Stage\DiffStage($repository, $io))
            ->execute()
        ;
    }

    /**
     * @return array
     */
    protected function getTargetGroups()
    {
        $config = $this->getConfig()->getConfig();

        $groups = array();
        foreach ($config['targets'] as $targetName => $targetConfig) {
            foreach ($targetConfig['groups'] as $group) {
                $groups[$group][] = $targetName;
            }
        }

        return $groups;
    }

    /**
     * @param string $target
     * @return bool
     */
    protected function isTargetGroup($target)
    {
        $groups = $this->getTargetGroups();

        return array_key_exists($target, $groups);
    }

    /**
     * @param string $group
     * @return array
     */
    protected function getTargetsForGroup($group)
    {
        if (!$this->isTargetGroup($group)) {
            throw new \InvalidArgumentException(
                sprintf(
                    '"%s" is not a valid target group, valid groups are: %s',
                    $group,
                    implode(', ', array_keys($this->getTargetGroups()))
                )
            );
        }

        $groups = $this->getTargetGroups();

        return $groups[$group];
    }

    /**
     * Deploys version to target
     *
     * @param string $target
     * @param string $version
     * @param array  $options
     */
    protected function doDeploy($target, $version, array $options = array())
    {
        $version = $this->getRepository()->getVersion($version);

        $options += array(
            'full_deploy'       => false,
            'deploy_after_only' => false,
            'force'             => false,
        );

        $this->assertTargetExists($target, $this->getConfig()->getConfig());

        $this->setConfigParametersForTarget($target);
        $this->setConfigParametersForVcs($this->getRepository(), $version);

        $config         = $this->getConfig()->getConfig();
        $derived        = $config['build']['derived'];
        $transporter    = $this->getTransporter($target);
        $builder        = $this->getBuilder();
        $repository     = $this->getRepository();
        $io             = $this->getIO();
        $dispatcher     = $this->container->get('dispatcher');
        $trDeployBefore = $this->container->get('deploy.taskrunner.before');
        /** @var $trDeployAfter TaskRunner */
        $trDeployAfter  = $this->container->get('deploy.taskrunner.after');
        $trDeployFinal  = $this->container->get('deploy.taskrunner.final');
        $remoteInfoFile = $this->container->getParameter('conveyor.remoteinfofile');
        $strategy       = $this->getStrategy($transporter);

        // @todo I don't like how the transporter is set here
        $trDeployBefore->setTransporter($transporter);
        $trDeployAfter->setTransporter($transporter);
        $trDeployFinal->setTransporter($transporter);

        $context = new Context();
        $context
            ->setFullDeploy($options['full_deploy'])
            ->setForce($options['force'])
            ->setBuilddir($builder->getBuildDir())
            ->setVersion($version)
            ->setTarget($target)
            ->setStrategy($strategy)
        ;

        if ($transporter instanceof TransactionalTransporterInterface) {
            $transporter->begin();
        }

        $manager = new StageManager($context, $dispatcher);
        $manager
            ->addStage('validate.remote',    new Stage\ValidateRemoteStage($transporter, $io, $remoteInfoFile))
            ->addStage('get.remote.version', new Stage\RetrieveRemoteVersionInfoStage($transporter, $repository, $io, $remoteInfoFile))
            ->addStage('build',              new Stage\BuildStage($builder, $io))
            ->addStage('filelist',           new Stage\BuildFilelistStage($repository, $derived))
            ->addStage('deploy.before',      new Stage\DeployBeforeStage($trDeployBefore, $io))
            ->addStage('transfer',           new Stage\TransferStage($transporter, $io))
            ->addStage('set.remote.version', new Stage\WriteRemoteInfoFileStage($transporter, $remoteInfoFile, $io))
            ->addStage('deploy.after',       new Stage\DeployAfterStage($trDeployAfter, $io))
            ->addStage('deploy.final',       new Stage\DeployFinalStage($trDeployFinal, $io))
        ;

        if ($transporter instanceof TransactionalTransporterInterface) {
            $dispatcher->addListener(
                StageEvents::STAGE_PRE_EXECUTE,
                function (StageEvent $event) use ($transporter) {
                    if ('deploy.after' === $event->getStageName()) {
                        $transporter->commit();
                    }
                }
            );
        }

        $runnableStages = array();
        if (true === $options['deploy_after_only']) {
            $runnableStages = array('deploy.after');
        }

        try {
            $result = $manager->execute($runnableStages);
        } catch (EmptyChangesetException $e) {
            $this->getIO()->write('Nothing to deploy: no files have been changed or removed. Use --full to deploy anyway.', true);

            $result = true; // trigger a cleanup
        }

        if ($result === true) {
            // cleanup
            $builddir = $this->getBuilder()->getBuildDir();
            if (is_dir($builddir)) {
                $filesystem = new Filesystem();
                $filesystem->remove($builddir);
            }
        }
    }

    /**
     * Simulates a deploy
     *
     * @param string $target
     * @param string $version
     * @param array  $options
     */
    protected function doSimulate($target, $version, array $options = array())
    {
        $version = $this->getRepository()->getVersion($version);

        $options += array(
            'full_deploy' => false,
            'force'       => false,
        );

        $this->assertTargetExists($target, $this->getConfig()->getConfig());

        $this->setConfigParametersForTarget($target);
        $this->setConfigParametersForVcs($this->getRepository(), $version);

        $transporter = $this->getTransporter($target);
        $readOnlyTransporter = $this->container->get('transporter.readonly');
        $readOnlyTransporter->setInnerTransporter($transporter);

        $config         = $this->getConfig()->getConfig();
        $derived        = $config['build']['derived'];
        $builder        = $this->getBuilder();
        $repository     = $this->getRepository();
        $io             = $this->getIO();
        $dispatcher     = $this->container->get('dispatcher');
        $trDeployBefore = $this->container->get('deploy.taskrunner.before');
        $trDeployAfter  = $this->container->get('deploy.taskrunner.after');
        $trDeployFinal  = $this->container->get('deploy.taskrunner.final');
        $remoteInfoFile = $this->container->getParameter('conveyor.remoteinfofile');
        $strategy       = $this->getStrategy($readOnlyTransporter);

        $trDeployBefore->setTransporter($readOnlyTransporter);
        $trDeployAfter->setTransporter($readOnlyTransporter);
        $trDeployFinal->setTransporter($readOnlyTransporter);

        $context = new Context();
        $context
            ->setFullDeploy($options['full_deploy'])
            ->setForce($options['force'])
            ->setSimulate(true)
            ->setBuilddir($this->getBuilder()->getBuildDir())
            ->setVersion($version)
            ->setTarget($target)
            ->setStrategy($strategy)
        ;

        if ($transporter instanceof TransactionalTransporterInterface) {
            $transporter->begin();
        }

        $manager = new StageManager($context, $dispatcher);
        $manager
            ->addStage('validate.remote',    new Stage\ValidateRemoteStage($readOnlyTransporter, $io, $remoteInfoFile))
            ->addStage('get.remote.version', new Stage\RetrieveRemoteVersionInfoStage($readOnlyTransporter, $repository, $io, $remoteInfoFile))
            ->addStage('build',              new Stage\BuildStage($builder, $io))
            ->addStage('filelist',           new Stage\BuildFilelistStage($repository, $derived))
            ->addStage('deploy.before',      new Stage\DeployBeforeStage($trDeployBefore, $io))
            ->addStage('transfer',           new Stage\TransferStage($readOnlyTransporter, $io))
            ->addStage('set.remote.version', new Stage\WriteRemoteInfoFileStage($readOnlyTransporter, $remoteInfoFile, $io))
            ->addStage('deploy.after',       new Stage\DeployAfterStage($trDeployAfter, $io))
            ->addStage('deploy.final',       new Stage\DeployFinalStage($trDeployFinal, $io))
        ;

        if ($transporter instanceof TransactionalTransporterInterface) {
            $dispatcher->addListener(
                StageEvents::STAGE_PRE_EXECUTE,
                function (StageEvent $event) use ($transporter) {
                    if ('deploy.after' === $event->getStageName()) {
                        $transporter->commit();
                    }
                }
            );
        }

        try {
            $result = $manager->execute();
        } catch (EmptyChangesetException $e) {
            $this->getIO()->write('Nothing to deploy: no files have been changed or removed. Use --full to deploy anyway.', true);

            $result = true; // trigger a cleanup
        }

        if ($result === true) {
            // cleanup
            $builddir = $this->getBuilder()->getBuildDir();
            if (is_dir($builddir)) {
                $filesystem = new Filesystem();
                $filesystem->remove($builddir);
            }
        }
    }

    /**
     * @param  IOInterface                          $io
     * @return ContainerBuilder
     * @throws \Exception|\InvalidArgumentException
     */
    protected function buildContainer(IOInterface $io)
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/Resources/config'));
        $loader->load('parameters.yml');
        $loader->load('services.yml');
        $loader->load('tasks.yml');
        $loader->load('transporters.yml');
        $loader->load('strategies.yml');

        $container->set('io', $io);

        $container->addCompilerPass(new TaskCompilerPass());
        $container->addCompilerPass(new TransporterCompilerPass());
        $container->addCompilerPass(new StrategyCompilerPass());
        $container->addCompilerPass(new ParameterCompilerPass());

        // set custom config file if it is set
        if ($this->configFile) {
            $container->setParameter('conveyor.configfile', $this->configFile);
        }

        try {
            $container->compile();
        } catch (\InvalidArgumentException $e) {
            // Ignore an InvalidArgumentException for YamlConfig,
            // otherwise init() would not be possible
            if ('YamlConfig.php' !== basename($e->getFile())) {
                throw $e;
            }
        }

        return $container;
    }

    /**
     * @param string $target
     */
    protected function setConfigParametersForTarget($target)
    {
        $this->getConfig()->setParameter('target', $target);

        $config = $this->getConfig()->getConfig();

        $transporterOptions = $config['targets'][$target]['transport'];
        foreach ($transporterOptions as $key => $value) {
            $this->getConfig()->setParameter('target.transport.' . $key, $value);
        }
    }

    /**
     * @param Repository $getRepository
     * @param Version $version
     */
    private function setConfigParametersForVcs(Repository $getRepository, Version $version)
    {
        $this->getConfig()->setParameter('vcs.url', $getRepository->getUrl());
        $this->getConfig()->setParameter('vcs.type', $getRepository->getType());
        $this->getConfig()->setParameter('vcs.build', $version->getBuild());
        $this->getConfig()->setParameter('vcs.build_short', substr($version->getBuild(), 0, 7));
        $this->getConfig()->setParameter('vcs.version', $version->getName());
    }

    /**
     * @param string $target
     * @param array $config
     * @return bool
     * @throws \InvalidArgumentException
     */
    protected function assertTargetExists($target, array $config)
    {
        if (!isset($config['targets'][$target])) {
            throw new \InvalidArgumentException(sprintf("Target '%s' does not exist, available targets are: %s.", $target, implode(", ", array_keys($config['targets']))));
        }

        return true;
    }

    /**
     * @param string $target
     * @param array $config
     * @return bool
     * @throws \InvalidArgumentException
     */
    protected function assertTargetOrGroupExists($target, array $config)
    {
        if (!isset($config['targets'][$target]) && !$this->isTargetGroup($target)) {
            $targets = array_keys($config['targets']);
            $groups = array_keys($this->getTargetGroups());

            throw new \InvalidArgumentException(sprintf("Target '%s' does not exist, available targets and groups are: %s.", $target, implode(", ", array_merge($targets, $groups))));
        }

        return true;
    }

    /**
     * Set a custom config file, is used for booting (compiling the di container)
     *
     * @param string $path
     */
    public function setConfigFile($path)
    {
        $this->configFile = $path;
    }
}
