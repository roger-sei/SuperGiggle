## Code convention validator for legacy codes written in PHP

![GitHub top language](https://img.shields.io/github/languages/top/roger-sei/SuperGiggle?style=for-the-badge)
![GitHub](https://img.shields.io/github/license/roger-sei/SuperGiggle?style=for-the-badge)
![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/roger-sei/SuperGiggle?style=for-the-badge)
![GitHub last commit](https://img.shields.io/github/last-commit/roger-sei/SuperGiggle?style=for-the-badge)

When working with a **legacy code** or an already existing huge code, validating the existing code may become a big nightmare and you nust can't improve the legacy code overnight.

![Full check using PHPCS](https://roger-sei.github.io/assets/phpcs.gif)

But at the same time you still WANT to implement **good practices** and **standardize**, at least, your new code. **SuperGiggle** is about checking just the new changes you have made in your repository, ignoring the already existing code. You can even use **super-giggle** in your CI tools as well.

![Full check using PHPCS](https://roger-sei.github.io/assets/super-giggle.gif)

## Requirements
- PHP 7.2 or greater
- PHPCS 3 or greater

# Instalation and usage

## Phar

    wget https://roger-sei.github.io/super-giggle.phar
    php super-giggle.phar --help

## Composer
    composer install roger-sei/super-giggle
    super-giggle --help

## Git clone

    git clone https://github.com/roger-sei/SuperGiggle.git && SuperGiggle
    composer install
    php bin/super-giggle --help

## Options
    bin/supper-giggle [--repo] [--commit] [options]
```
    --all.     Checks the whole file. Same as *git diff [file]*.
    --repo     The working git repository.
    --commit   The specific commit to validate.
    --phpcs    Path to phpcs executable, required only if not using composer or if phpcs isn't in your PATH env.
    --type     <show|diff> *diff* validates the local changes. *show* validate changes in a specific commit.
    --file     Force checking this file, regardless the type or commit options.
    --diff     Validates changes on the current repository, between commits or branches.
    --verbose  Prints additional information.
    --warnings Displays also warnings and not only errors.
    --standard The name or path of the coding standard. Defaults to PSR12.
```
