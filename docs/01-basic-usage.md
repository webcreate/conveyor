Basic usage
===========

## Installation

You can download Conveyor from the [download page](http://conveyordeploy.com/download.html).

After you have installed Conveyor you can run it as follows:

	$ php conveyor.phar

This will give you an overview of the available commands.

## Project setup

To deploy a project with Conveyor you need a `conveyor.yml` configuration file in the root of your project.

You can initialize a new configuration file by issuing the `init` command.

    $ php conveyor.phar init

The [format of the configuration file](02-schema.md) is quite simple.

### Repository

Conveyor works with the code in your version control system. The repository is used to find available tags
and branches for deployment. It also does a checkout of your codebase in the build process.

    repository:
      type: git
      url:  git@github.com:acme/example.git

### Target definitions

With the `targets` key you tell Conveyor which targets are available for deployment.

    targets:
      production:
        transport:
          type: sftp
          host: example.com
          path: /var/www/example.com
          user: user

You can then use the target name (in the above case "production") in the `deploy` commands. For example:

    $ php conveyor.phar deploy production 1.0.2

### Build tasks

Before Conveyor starts with deploying, it first creates a build. The build process consists out of
a number of tasks that you can specify. The build process always starts with exporting the codebase
from the version control system.

    build:
      tasks:
        -
          type:    shell
          command: composer install

## Listing available versions

Conveyor reads the available versions from your repositories' tags and branches. You can list the available
versions that you can deploy by issuing the following command:

    $ php conveyor.phar version

This will list the available versions and their revision number.

### Tags

For every tag in your repository a version is available for deployment. It should match 'X.Y.Z' or 'vX.Y.Z',
with an optional suffix for RC, beta, alpha or patch.

Here are a few examples of valid tag names:

    1.0.0
    v1.0.0
    1.10.5-RC1
    v4.4.4beta2
    v2.0.0-alpha
    v2.0.4-p1

### Branches

For every branch in your repository a development version is available. These are prefixed with `dev-{branchname}`. This
is because it is not recommended to deploy a state of your project that is in development. Only use this for
testing purposes.

## Show the status for each target

To quickly get a status overview about each target, run the following command:

    $ php conveyor.phar status

This will list the deployed version and build for each target and tells you if the target is up-to-date, ahead or
behind on your local version.

## Creating a build

The normal deploy process consists of a build step and a transfer step. It is also possible to only run
the build step by issuing the following command:

    $ php conveyor.phar build <target> <version>

This creates a build in the given build directory.

## Simulating a deploy

With Conveyor you can simulate a deploy with the following command:

    $ php conveyor.phar simulate <target> <version>

This will create a build and show you the files that would be uploaded if this was a
real deploy.

## Deploying

To deploy a new version to a specific target you can issue the following command:

    $ php conveyor.phar deploy <target> <version>
