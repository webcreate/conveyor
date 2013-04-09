Conveyor - Build and deploy tool for PHP
========================================

Conveyor is a build and deploy for PHP projects.

More information will be available at http://conveyordeploy.com

Licensing
---------

Conveyor is free for personal and educational use.

When used for commercial projects a purchase of a license is required per developer seat
([why?](http://webcreate.nl/conveyor/license#why)). Commercial use includes any project
that makes you money. Commercial licenses may be purchased in the future at http://conveyordeploy.com

Installation from source
------------------------

To run tests, or to contribute to Conveyor, you must use the sources and not the phar
file as described above.

1. Run `git clone https://github.com/webcreate/conveyor.git`
2. Run [Composer](http://getcomposer.org/) to get the dependencies: `cd conveyor && php composer.phar install`

You can now run Conveyor by executing the `bin/conveyor` script: `php /path/to/conveyor/bin/conveyor`
