## About
SuperGiggle checks for code violations in a given commit, checking only changes regarding the given commit.

## Requires
- PHP 7.2 or greater


# Instalation
## Download using curl
    PHAR: [https://roger-sei@github.io/super-giggle.phar]
    php super-giggle.phar --help


## Composer
    composer install *roger-sei/super-giggle*


## Git clone
    git clone https://github.com/roger-sei/SuperGiggle.git
    cd SuperGiggle
    php bin/super-giggle --help


# Usage
## Basic
- bin/supper-giggle [--repo] [--commit] [options]


## Options
bin/supper-giggle [--repo] [--commit] [options]
- --all    Check the whole file. Same as *git diff*.
- --repo   The working git repository.
- --commit The specific commit to validate.
- --type   <show|diff> *diff* validate the local changes. *show* validate changes in a specific commit.
- --file   Force checking this file, regardless the type or commit options.
