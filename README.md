## About
SuperGiggle checks for code violations in a given commit, checking only changes regarding the given commit.

## Requirements
- PHP 7.2 or greater
- PHPCS 3 or greater


# Instalation and usage

## Download using curl
PHAR: [super-giggle.phar](https://roger-sei.github.io/super-giggle.phar)
super-giggle.phar](https://roger-sei.github.io/super-giggle.phar)

    php super-giggle.phar --help


## Composer
    composer install roger-sei/super-giggle
    super-giggle --help


## Git clone

    git clone https://github.com/roger-sei/SuperGiggle.git && SuperGiggle
    php bin/super-giggle --help


## Options
    bin/supper-giggle [--repo] [--commit] [options]
- **---all** Check the whole file. Same as *git diff*.
- **---repo** The working git repository.
- **---commit** The specific commit to validate.
- **---phpcs** Path to phpcs executable, required if not using composer and phpcs isn't on PATH env.
- **---type** <show|diff> *diff* validate the local changes. *show* validate changes in a specific commit.
- **---file** Force checking this file, regardless the type or commit options.
- **---diff** Validate changes on the current repository, between commits or branches.
- **---verbose** Prints additional information.
- **---warnings** Also displays warnings.
- **---standard** The name or path of the coding standard to use.
