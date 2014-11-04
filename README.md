Conveyor
========

Conveyor is a build and deploy tool written in PHP for PHP projects.

Full documentation is available at [http://conveyordeploy.com](http://conveyordeploy.com)

[![Build Status](https://travis-ci.org/webcreate/conveyor.svg?branch=master)](https://travis-ci.org/webcreate/conveyor)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/webcreate/conveyor/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/webcreate/conveyor/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/webcreate/conveyor/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/webcreate/conveyor/?branch=master)

Installation / Usage
--------------------

**Note:** The phar is very outdated, please follow the instructions for installation from source.

1. Download the [`conveyor.phar`](http://conveyordeploy.com/download.html) executable.
2. Create a conveyor.yml by running: `php conveyor.phar init`
3. Read the [docs](http://conveyordeploy.com/docs) on how to configure Conveyor for your project

Installation from source
------------------------

To run tests, or to contribute to Conveyor, you must use the sources and not the phar
file as described above.

1. Run `git clone https://github.com/webcreate/conveyor.git`
2. Run [Composer](http://getcomposer.org/) to get the dependencies: `cd conveyor && php composer.phar install`

You can now run Conveyor by executing the `bin/conveyor` script: `php bin/conveyor`

Contributing
------------

All code contributions must go through a pull request and approved by a core developer
before being merged. This is to ensure proper review of all the code.

Fork the project, **create a feature branch**, and send us a pull request.

To ensure a consistent code base, you should make sure the code follows
the [Coding Standards](http://symfony.com/doc/current/contributing/code/standards.html)
which we borrowed from Symfony.

Licensing
---------

Conveyor is released under the terms of the MIT License.

Acknowledgments
---------------

- This project is heavily inspired by the [Symfony](https://github.com/symfony/symfony) and [Composer](https://github.com/composer/composer) projects.
