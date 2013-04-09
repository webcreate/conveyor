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

## build

Build configuration. Also see chapter [Build](03-build.md).

### dir

Builddir.

### tasks

Array of build tasks.

#### type

Type of task. See [Build tasks](03-build.md#tasks) for available tasks.

### deploy

#### before

Array of tasks that are executed on the remote host before the build is uploaded.

##### type

Type of task. See [Build tasks](03-build.md#tasks) for available tasks.

#### after

Array of tasks that are executed on the remote host after the build is uploaded.

##### type

Type of task. See [Build tasks](03-build.md#tasks) for available tasks.
