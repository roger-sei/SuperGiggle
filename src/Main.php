<?php
/**
 * Main class for SuperGiggle, with auto runner option available.
 *
 * PHP Version 7.3
 *
 * @category  PHP
 * @package   GT8
 * @author    GT8 <roger.sei@icloud.com>
 * @copyright 2020 Roger Sei
 * @license   //github.com/roger-sei/SuperGiggle/blob/master/LICENSE MIT
 * @version   Release: GIT: 0.1.0
 * @link      //github.com/roger-sei/SuperGiggle
 */

namespace SupperGiggle;

class Main
{

    /**
     * Errors matched between git show and phpcs.
     *
     * @var array
     */
    private $filesMatched = [];

    /**
     * Friendly separator displayed in terminal.
     *
     * @var string
     */
    private $separator = PHP_EOL;

    /**
     * Arguments from CLI.
     *
     * @var array
     */
    private $options = [];

    /**
     * Indicates whether it has found error or not.
     *
     * @var bool
     */
    public $errorFound = false;

    /**
     * Indicates whether this is a phar execution or not.
     *
     * @var bool
     */
    public $isPhar = false;


    /**
     * Sets optional settings.
     *
     * @return void
     */
    public function __construct()
    {
        $this->separator = str_repeat('-', 110) . PHP_EOL;
    }


    /**
     * Run SupperGiggle, using arguments from CLI.
     *
     * @param array $args Arguments in CLI format.
     *
     * @return void
     */
    public function autoRun(array $args): void
    {
        next($args);
        $options      = [];
        $solos        = [];
        $solosAllowed = [
            '--all',
            '--verbose',
            '--diff',
        ];
        for ($arg = current($args); $arg; $arg = next($args)) { // phpcs:ignore
            if (substr($arg, 0, 2) === '--' && strlen($arg) > 2) {
                if (strpos($arg, '=') !== false) {
                    preg_match('#--([\\w\\s\-]+)=("|\')?(.+)(\\2)?#', $arg, $results);
                    if (isset($results[2]) === true) {
                        $options[$results[1]] = $results[3];
                    } else {
                        $message  = "Malformed argument '$arg'. ";
                        $message .= 'Check your syntax and try again or make a pull request to fix any error :P';
                        $this->exit($message);
                    }
                } elseif (in_array($arg, $solosAllowed) === true) {
                    $options[substr($arg, 2)] = true;
                } else {
                    $options[substr($arg, 2)] = next($args);
                }
            } else {
                $solos[] = $arg;
            }
        }

        $options['commit'] = ($options['commit'] ?? end($solos) ?? '');

        $this->run($options);
    }


    /**
     * Helper to display a message and exit.
     *
     * @param string $message Error message.
     *
     * @return void
     */
    private function exit(string $message = ''): void
    {
        $errors   = explode(PHP_EOL, $message);
        $errors[] = "\n  Try ``--help`` for more information.";

        $title = array_shift($errors);

        foreach ($errors as &$error) {
            $error = '  ' . preg_replace_callback(
                '#(``).+?(``)#',
                function ($matches) {
                    return "\033[1;35m" . substr($matches[0], 2, -2) . "\033[m";
                },
                $error
            );
        }

        echo PHP_EOL;
        echo "  \033[0;31m$title\033[0m\n";
        echo join(PHP_EOL, $errors);
        echo PHP_EOL;
        echo PHP_EOL;
        exit(1);
    }


    /**
     * Helper to display a message and exit.
     *
     * @param boolean $assertion Conditional to exit.
     * @param string  $message   Error message.
     *
     * @return void
     */
    private function exitIf(bool $assertion, string $message): void
    {
        if ($assertion === true) {
            $this->exit($message);
        }
    }


    /**
     * Helper to match a given string using regex.
     *
     * @param string $text  The input string.
     * @param string $regex The pattern to search for.
     *
     * @return string The first string captured.
     */
    protected function match(string $text, string $regex): string
    {
        preg_match("#$regex#", $text, $result);
        return ($result[1] ?? $result[0] ?? '');
    }


    /**
     * Performs a git show and parse the results.
     *
     * @return array The parsed results in a bidimensional array.
     */
    private function parseModifiedGitFiles(): array
    {
        $repo   = $this->options['repo'];
        $type   = $this->options['type'];
        $commit = $this->options['commit'];
        $file   = $this->options['file'];

        $files = [];
        if (isset($this->options['all']) === true && isset($this->options['file']) === true) {
            $files[$this->options['file']] = [];
        } else {
            $result  = shell_exec("git --git-dir=$repo/.git --work-tree=$repo $type $commit --unified=0 $file | egrep '^(@@|\+\+)'");
            $lines   = explode(PHP_EOL, $result);
            $crrFile = null;
            foreach ($lines as $line) {
                if (substr($line, 0, 3) === '++ ' || substr($line, 0, 4) === '+++ ') {
                    $crrFile         = substr($line, (strpos($line, ' b/') + 3));
                    $files[$crrFile] = [];
                } elseif (substr($line, 0, 3) === '@@ ' || substr($line, 0, 4) === '@@@ ') {
                    preg_match('/^@@ .+? \+(\d+),?(\d+)?/', $line, $numbers);
                    if (isset($numbers[1]) === true) {
                        $files[$crrFile][] = [
                            'status' => false,
                            'line'   => $numbers[1],
                            'range'  => ($numbers[2] ?? 0),
                            'change' => substr($line, (strpos($line, ' ') + 1)),
                        ];
                    }
                }
            }
        }

        return $files;
    }


    /**
     * Validates the given file using PHPCS
     *
     * @param string $file PHP file.
     *
     * @return array List of files
     */
    private function parsePHPCSErrors(string $file): array
    {
        $stndr    = $this->options['standard'];
        $php      = $this->options['php'];
        $phpcs    = $this->options['phpcs'];
        $warnings = $this->options['warnings'];

        $response = shell_exec("$php $phpcs --report=json --standard=$stndr '$file' $warnings");
        // Some encoding issues makes PHPCS return empty object, causing invalid JSON.
        // This is a quick fix.
        $json = json_decode(str_replace('},,{', '},{', $response), true);
        if (empty($json['files']) === false) {
            return current($json['files'])['messages'];
        } else {
            return [];
        }
    }


    /**
     * Outputs a friendly error message to the console
     *
     * @param string $file  The parsed.
     * @param array  $error The object error, from PHPCS.
     *
     * @return void
     */
    private function printError(string $file, array $error): void
    {
        $this->errorFound = true;

        if (isset($this->filesMatched[$file]) === false) {
            $this->filesMatched[$file] = true;
            echo "\n  FILE: $file\n";
            echo $this->separator;
            echo '';
        }

        if (isset($this->options['verbose']) === true) {
            $verbose = ' | ' . str_pad($error['type'], 10, ' ', STR_PAD_RIGHT);

            if (strlen($error['source']) > 60) {
                $verbose .= ' | ' . substr($error['source'], 0, 57) . '...';
            } else {
                $verbose .= ' | ' . str_pad($error['source'], 60, ' ', STR_PAD_RIGHT);
            }
        } else {
            $verbose = '';
        }

        echo str_pad($error['line'], 9, ' ', STR_PAD_LEFT);
        echo $verbose;
        echo ' | ' . $error['message'] . PHP_EOL;
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
            'standard' => 'The name or path of the coding standard to use',
            'diff'     => 'Validate changes on the current repository, between commits or branches',
            'all'      => 'Performs a full check and not only the changed lines',
            'repo'     => 'Indicates the git working directory. Defaults to current cwd',
            'phpcs'    => 'Indicates the php binary. Defaults to ENV',
            'type'     => 'The type of check. Defaults to "show" changes of a given commit. ',
            'help'     => 'Print this help',
            'verbose'  => 'Prints additional information',
            'warnings' => 'Also displays warnings',
        ];
        foreach ($options as $name => $description) {
            echo str_pad("\033[1;31m  --$name ", 22, ' ', STR_PAD_RIGHT) .
                "\033[1;37m" . $description . "\033[0m" . PHP_EOL;
        }

        echo PHP_EOL;

        exit(0);
    }


    /**
     * Run the SuperGiggle using $options
     *
     * @param array $options Check help for more information.
     *
     * @return void
     */
    public function run(array $options = null): void
    {
        $this->options = ($options ?? $this->options);
        if (isset($this->options['help']) === true) {
            $this->printUsage();
        }

        $this->validateOptions();

        $files    = $this->parseModifiedGitFiles();
        $checkAll = isset($this->options['all']);
        $checkSBC = 'Function closing brace must go on the next line';
        foreach ($files as $file => $gitChanges) {
            foreach ($this->parsePHPCSErrors("{$this->options['repo']}/$file") as $crrPhpcsError) {
                print_r($crrPhpcsError, true);
                if ($checkAll === true) {
                    $this->printError($file, $crrPhpcsError);
                } else {
                    foreach ($gitChanges as $crrChange) {
                        if ($crrPhpcsError['line'] >= $crrChange['line']
                            && $crrPhpcsError['line'] <= ($crrChange['line'] + $crrChange['range'])
                        ) {
                            $this->printError($file, $crrPhpcsError);
                        } elseif (($crrPhpcsError['line'] + 1) >= $crrChange['line']
                            && $crrPhpcsError['line'] <= ($crrChange['line'] + $crrChange['range'])
                        ) {
                            // Check for errors right after the line changed.
                            // @gregsherwood suggestion for a better approach?
                            if (strpos($crrPhpcsError['message'], $checkSBC) !== false) {
                                $this->printError($file, $crrPhpcsError);
                            }
                        }
                    }
                }
            }
        }

        if ($this->errorFound === true) {
            echo PHP_EOL;
            exit(1);
        }
    }


    /**
     * Validate all required fieldsand exit if it fails.
     *
     * @return void
     */
    private function validateOptions(): void
    {
        $base = dirname(__DIR__);

        // First, we check for basic system requirements.
        if ($this->isPhar === true) {
            if (isset($this->options['phpcs']) === true) {
                $base = getcwd();
                $this->exitIf(
                    empty(shell_exec("command -v $base/{$this->options['phpcs']}")),
                    "'$base/{$this->options['phpcs']}' not valid phpcs command. Please, make sure it exists."
                );
            } else {
                $this->exitIf(
                    empty(shell_exec('command -v phpcs')),
                    "'phpcs' is required when using phar.\n\nPlease, install it or use ``--phpcs`` option to indicate the path."
                );
            }
        } else {
            $this->exitIf(
                isset($this->options['phpcs']) && empty(shell_exec("command -v $base/{$this->options['phpcs']}")),
                "'phpcs' not found. Please, install it or use ``phpcs`` option to indicate the path"
            );
            $this->exitIf(
                (isset($this->options['phpcs']) && !file_exists("$base/vendor/squizlabs/php_codesniffer/bin/phpcs")),
                "Dependency file 'phpcs' not found. Please, install it using composer or use ``--phpcs`` option to indicate the executable"
            );
        }

        $this->exitIf(
            (version_compare(PHP_VERSION, '7.1') === -1),
            'super-giggle requires at leaset PHP 7.1. Your PHP version is ' . PHP_VERSION . ' :('
        );

        // Now, check for all options.
        if (isset($this->options['diff']) === true) {
            $this->options['type'] = 'diff';
        }

        if (isset($this->options['warnings']) === true) {
            $this->options['warnings'] = '--warning-severity=5';
        } else {
            $this->options['warnings'] = '--warning-severity=9';
        }

        $this->options['type']     = ($this->options['type'] ?? 'show');
        $this->options['php']      = ($this->options['php'] ?? 'php');
        $this->options['phpcs']    = ($this->options['phpcs'] ?? __DIR__ . '/../vendor/bin/phpcs');
        $this->options['standard'] = ($this->options['standard'] ?? 'PSR12');

        if (empty($this->options['repo']) === true && file_exists(getcwd() . '/.git') === true) {
            $this->options['repo'] = getcwd();
        } elseif (empty($this->options['repo']) === true) {
            if (isset($this->options['repo']) === true) {
                $this->exit('Empty value for ``--repo``');
            } else {
                if (preg_match('#^(.+)\.git#i', shell_exec('git rev-parse --git-dir'), $result) === 1
                    && isset($result[1]) === true
                ) {
                    $this->options['repo'] = $result[1];
                } else {
                    $this->exit('Missing ``--repo``');
                }
            }
        }

        $this->exitIf(file_exists($this->options['repo']) === false, "Directory \"{$this->options['repo']}\" not found");

        if (empty($this->options['commit']) === true && empty($this->options['file']) === true) {
            if ($this->options['type'] === 'show') {
                if (isset($this->options['repo']) === true) {
                    $repo   = $this->options['repo'];
                    $arg    = "git --git-dir=$repo/.git --work-tree=$repo log --oneline --color | head -n 10";
                    $result = shell_exec($arg);
                    $error  = "Missing --commit.\n\nPlease, choose a commit, ";
                    $error .= 'specify a file using ``--file`` option, or ';
                    $error .= "use ``--diff`` option to validate against the lastest changes.\n\n";
                    $error .= "Available commits:\n\n$result";
                    $this->exit($error);
                }
            } elseif ($this->options['type'] === 'diff') {
                $this->options['commit'] = ($this->options['commit'] ?? '');
            } else {
                $this->exit('Invalid value for ``--type``.');
            }
        } else {
            $this->options['commit'] = ($this->options['commit'] ?? '');
        }

        $this->options['file'] = ($this->options['file'] ?? '');

        $this->exitIf(
            (empty($this->options['file']) === false && file_exists($this->options['file']) === false),
            "File '{$this->options['file']}' doesn't appear to exist!"
        );
    }


}
