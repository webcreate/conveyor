conveyor.yml
============

This document will describe the properties of the `conveyor.yml` configuration file.

## repository

### type

The type of the repository. Available values:

* git
* svn

### url

Url of the repository. For example:

    https://github.com/webcreate/conveyor.git

## targets

Contains the available deploy targets for your project. For example:

* testing
* acceptance
* production

### url

Url of the target environment.

### transport

#### type

See [Transporters](04-transport.md#transporters) for available transporters.

Available values:

* sftp
* ftp
* rsync
* file
* git

#### host

Hostname of the target environment.

#### path

Path of your application on the target environment.

#### user

Username.

#### password

Password.

### parameters

Allows to set target specific parameters which you can use in targets.

For example:

```yaml
parameters:
  symfony_env: prod
  composer_flags: --optimize-autoloader --no-dev
```

This allows you to use this in a task:

```yaml
-
  type: ssh
  command: SYMFONY_ENV={{target.symfony_env}} composer install {{target.composer_flags}}
```

### groups

You can add multiple targets to one or more groups and deploy to those targets in one run.

## build

Build configuration. Also see chapter [Build](03-build.md).

### dir

Builddir.

### derived

_Note: This feature is experimental and might be changed or removed in future versions_

Specify derived files and folders.

On default Conveyor does an incremental deploy: only changed files are uploaded. But when some
files are changed the implication is that other files should also be updated.

Here is an example for the Composer lock file.

    derived:
      - { source: composer.lock, derived: vendor/ }

If the `composer.lock` file is changed, all files in the directory `vendor` will be uploaded.

### tasks

Array of build tasks.

#### type

Type of task. See [Build tasks](03-build.md#tasks) for available tasks.

### deploy

#### strategy

Deploy strategy. See [Deploy](05-deploy.md#strategy) for more information.

#### before

Array of tasks that are executed (on the remote host) before the build is uploaded. See
[Deploy](05-deploy.md#before) for more information.

##### type

Type of task. See [Build tasks](03-build.md#tasks) for available tasks.

#### after

Array of tasks that are executed (on the remote host) after the build is uploaded.See
[Deploy](05-deploy.md#after) for more information.

##### type

Type of task. See [Build tasks](03-build.md#tasks) for available tasks.

#### final

Array of tasks that are executed (on the remote host) after the build is uploaded and the deploy
strategy is finished. See [Deploy](05-deploy.md#final) for more information.

##### type

Type of task. See [Build tasks](03-build.md#tasks) for available tasks.
