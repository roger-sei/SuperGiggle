## Code convention validator for legacy codes written in PHP

![GitHub top language](https://img.shields.io/github/languages/top/roger-sei/SuperGiggle?style=for-the-badge)
![GitHub](https://img.shields.io/github/license/roger-sei/SuperGiggle?style=for-the-badge)
![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/roger-sei/SuperGiggle?style=for-the-badge)
![GitHub last commit](https://img.shields.io/github/last-commit/roger-sei/SuperGiggle?style=for-the-badge)

When working with a **legacy code** or an already existing huge code, validating it may become a huge nightmare and you just can't improve the legacy code overnight.

![Full check using PHPCS](https://roger-sei.github.io/assets/phpcs.gif)

But at the same time you still WANT to implement **good practices** and **standardize**, at least, your new code. **SuperGiggle** is about checking just the new changes you have made in your repository, ignoring the already existing code. You can even use **super-giggle** in your CI tools as well.

![Full check using PHPCS](https://roger-sei.github.io/assets/super-giggle.gif)

## Requirements
- PHP 7.2 or greater
- PHPCS 3 or greater
- Linux/Windows

# Instalation and usage

## Phar

    wget https://roger-sei.github.io/super-giggle.phar
    php super-giggle.phar --help

## Composer
    composer global require roger-sei/super-giggle
    # Export composer path to your enviroment path, if it isn't exported.
    # Notice however it may have different path accordingly to composer version.
    # Check **composer global --help** for more information. 
    export PATH=$PATH:$HOME/.config/composer/vendor/bin/
    super-giggle --help

## Git clone

    git clone https://github.com/roger-sei/SuperGiggle.git && cd SuperGiggle
    composer install
    php bin/super-giggle --help

## Options
    bin/super-giggle [--repo] [--commit] [options]
```
    --all      Checks the whole file. Same as *git diff [file]*.
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

## Run tests
```bash
$ composer test
```

### License

```
The MIT License (MIT)

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```
