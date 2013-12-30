Deploy
======

Deploy stage.

## Strategies

Available strategies:

* `simple` deploys to a single folder on the remote server
* `releases` creates a 'releases' folder with latest releases and symlinks the latest version to
a folder called 'current'

### simple

This strategy places the files directly in the target folder as specified in the `transport`
section. Existing files will be overwritten.

### releases

This strategy creates the following directory structure on the server:

    `-- /path/to/app
        `-- current -> releases/1.3.2
        `-- releases
        |   `-- 1.2.3
        |   `-- 1.3.1
        |   `-- 1.3.2
        |   `-- dev-master-34ed24c
        `-- shared

The releases strategy has a number of configuration options:

* `shared`: an array of directories or files that will be shared between releases using symlinks
* `keep`: number of releases to keep (default: 5), the oldest releases will be removed

Example configuration:

    deploy:
      strategy:
        type: releases
        keep: 5
        shared:
          - app/config/parameters.yml
          - app/logs/

## Before

Run tasks before transfer starts.

Example:

    deploy:
      before:
        -
          type: ssh
          command: bin/activate_maintenance.sh

## After

Run tasks after transfer is completed.

Example:

    deploy:
      after:
        -
          type: ssh
          command: bin/update_db.sh

## Final

Run tasks just before the complete deploy run is finished. At this step the files are transferred,
the deploy after tasks have all been runned and the strategy is finished (e.g. in case of the
releases [strategy](05-deploy.md#strategy): after symlinking the release folder to the current
folder).

Run final tasks.

Example:

    deploy:
      final:
        -
          type: ssh
          command: service apache2 restart
