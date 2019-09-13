## About
SuperGiggle checks for code violations in a given commit, checking only changes regarding the given commit.

## Requires
- PHP 7.2 or greater

# Instalation
## Download using curl
https://github....

## Composer
composer install *roger-sei/supper-giggle*

## Git clone
    git clone https://github.com/roger-sei/SuperGiggle.git
    cd SupperGiggle
    php bin/supper-giggle --help

# Usage
## Basic
bin/supper-giggle [--repo] [--commit]

## Options
bin/supper-giggle [--repo] [--commit] [-check-type] [--all]
- --all        Check the whole file. Same as *git diff*.
- --repo       The working git repository.
- --commit     The specific commit to validate.
- --check-type <show|diff> *diff* validate the local changes. *show* validate changes in a specific commit.
