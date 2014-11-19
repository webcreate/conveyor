Build
=====

Conveyor can build your project before it gets deployed.

## Tasks

Tasks are executed in the order they are defined. The first task is always the ExportTask.

### ExportTask

This task will export your codebase. This task is always executed before any other task that is
defined.

You don't have to define this task in your `conveyor.yml` configuration.

### ShellTask

The ShellTask is a primitive task which will run a task on the commandline. It is very flexible
and can handle almost every task. Although it's recommended to use task that have been developed
for a specific task, instead of using the ShellTask.

You could for example call ant or phing with the ShellTask, but you get better results to use the
AntTask or PhingTask.

Available options:

- `command`: the shell command to be executed

Example:

	build:
	  tasks:
	    -
		  type: shell
		  command: rm -rf /temp/dir

### PharTask

The PharTask can create a Phar file from your codebase.

Available options:

- `filename`: name of the phar file that will be created
- `stub`: initial file to be called when the archive is executed (read
[more](http://php.net/manual/en/phar.fileformat.stub.php))

Example:

	build:
	  tasks:
	    -
		  type: phar
		  filename: conveyor.phar
		  stub: bin/conveyor

### SshTask

The SshTask can be used to run a command on the remote server.

Example:

	build:
	  tasks:
	    -
		  type: ssh
		  command: php app/console cache:clear

### PhingTask

Task to run Phing.

Available options:

- `target`: can be a string or an array of multiple targets
- `buildfile`: name of the build file to use

Example:

	build:
	  tasks:
	    -
		  type: phing
		  target: [test, dist]
		  buildfile: custom_build.xml

### RemoveTask

Task to remove files.

Example:

	build:
	  tasks:
	    -
		  type: remove
		  target: [test, dist]
		  files: 
		    - web/app_dev.php
		    - web/app_test.php
		    - tests/

You can also exclude files. The following task removes all files execpt the app, src and web folders:

	build:
	  tasks:
	    -
		  type: remove
		  target: [test, dist]
		  files: '*'
		  exclude:
		    - app/
		    - src/
		    - web/
		    

