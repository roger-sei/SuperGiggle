<?php

/**
 * Util class for SuperGiggle
 *
 * PHP Version 7.3
 *
 * @category  PHP
 * @package   GT8
 * @author    girorme <rodrigogirorme@gmail.com>
 * @copyright 2020 Roger Sei
 * @license   //github.com/roger-sei/SuperGiggle/blob/master/LICENSE MIT
 * @version   Release: GIT: 0.5.0
 * @link      //github.com/roger-sei/SuperGiggle
 */

namespace SuperGiggle;

class Util
{

    /**
     * Os util
     *
     * @var Os
     */
    public $os;

    /**
     * The current version.
     *
     * @var string
     */
    const VERSION = '0.5.0';


    /**
     * Get phpcs binary
     *
     * @return string
     */
    public function getPhpCsBinary(): string
    {
        $path = __DIR__ . '/../vendor/bin/phpcs';

        if ($this->os->isWindows() === true) {
            $path = str_replace('\\', '/', __DIR__ . '/../vendor/bin/phpcs.bat');
        }

        return $path;
    }


    /**
     * Parse args and return in a friendly format
     *
     * @return array
     */
    public function parseArgs(): array
    {
        $alloweds = [
            'all',
            'commit:',
            'diff',
            'diff-cached',
            'everything',
            'file:',
            'help',
            'json',
            'php::',
            'php-version::',
            'phpcs:',
            'repo:',
            'standard:',
            'verbose::',
            'version',
            'warnings::',
        ];

        $opt = getopt('', $alloweds);
        array_walk($opt, function (&$value) {
            $value = ($value === false) ? true : $value;
        });

        return $opt;
    }


    /**
     * Print help information, in cli format
     *
     * @return void
     */
    public function printUsage(): void
    {
        echo "  Usage: \033[0;35msuper-giggle [--commit]\033[0m\n\n";
        $options = [
            'all'         => 'Performs a full check and not only the changed lines.',
            'commit'      => 'Checks agains a specifi commit.',
            'diff'        => 'Validate changes on the current repository, between commits or branches.',
            'diff-cached' => 'Check changes on staged files, alongside with --diff.',
            'everything'  => 'Checks changes in the whole project.',
            'file'        => 'Checks changes for the specific given file.',
            'help'        => 'Print this help.',
            'phpcs'       => 'Indicates the php binary. Defaults to ENV.',
            'php-version' => 'Checks the code accordingly to a specified PHP version.',
            'repo'        => 'Indicates the git working directory. Defaults to current cwd.',
            'standard'    => 'The name or path of the coding standard to use.',
            'type'        => 'The type of check. Defaults to "show" changes of a given commit.',
            'verbose'     => 'Prints additional information.',
            'version'     => 'Displays current super-giggle version.',
            'warnings'    => 'Also displays warnings.',
            'json'        => 'Display the results in JSON format',
        ];
        foreach ($options as $name => $description) {
            echo str_pad("\033[1;31m  --$name ", 24, ' ', STR_PAD_RIGHT) .
                "\033[1;37m" . $description . "\033[0m" . PHP_EOL;
        }

        echo PHP_EOL;

        exit(0);
    }


    /**
     * Print current super-giggle version, as in Util::VERSION.
     *
     * @return void
     */
    public function printVersion(): void
    {
        echo ' super-giggle version ' . self::VERSION . " \n\n";
        exit(0);
    }


    /**
     * Set current operating system.
     *
     * @param Os $os Os class.
     *
     * @return void
     */
    public function setOs(Os $os): void
    {
        $this->os = $os;
    }


}
