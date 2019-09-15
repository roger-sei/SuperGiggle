## About
SuperGiggle checks for code violations in a given commit, checking only for changed lines in a given commit, instead of checking for violations in the whole file.

## Requirements
- PHP 7.2 or greater
- PHPCS 3 or greater


# Instalation and usage

## Download using curl
PHAR: [super-giggle.phar](https://roger-sei.github.io/super-giggle.phar)

    php super-giggle.phar --help


## Composer
    composer install roger-sei/super-giggle
    super-giggle --help


## Git clone

    git clone https://github.com/roger-sei/SuperGiggle.git && SuperGiggle
    php bin/super-giggle --help


## Options
    bin/supper-giggle [--repo] [--commit] [options]
- **---all** Checks the whole file. Same as *git diff [file]*.
- **---repo** The working git repository.
- **---commit** The specific commit to validate.
- **---phpcs** Path to phpcs executable, required only if not using composer or if phpcs isn't in your PATH env.
- **---type** <show|diff> *diff* validates the local changes. *show* validate changes in a specific commit.
- **---file** Force checking this file, regardless the type or commit options.
- **---diff** Validates changes on the current repository, between commits or branches.
- **---verbose** Prints additional information.
- **---warnings** Displays also warnings and not only errors.
- **---standard** The name or path of the coding standard. Defaults to PSR12.
