<?php

/**
 * Main class for SuperGiggle, with auto runner option available.
 *
 * PHP Version 7.3
 *
 * @category PHP
 * @package  GT8
 * @author   Roger Sei <roger.sei@icloud.com>
 * @author   Rodrigo Girorme <rodrigo.girorme@gmail.com>
 * @license  //github.com/roger-sei/SuperGiggle/blob/master/LICENSE MIT
 * @version  Release: GIT: 0.7.1
 * @link     //github.com/roger-sei/SuperGiggle
 */

namespace SuperGiggle;

class Main
{

    /**
     * Errors matched between git show and phpcs.
     *
     * @var array
     */
    private $filesMatched = [];

    /**
     *
     *
     */
    private $json = null;

    /**
     * Arguments from CLI.
     *
     * @var array
     */
    private $options = [];

    /**
     * Friendly separator displayed in terminal.
     *
     * @var string
     */
    private $separator = PHP_EOL;

    /**
     * Util class
     *
     * @var Util
     */
    private $util;

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
     * Returns a list of all files in the project.
     *
     * @return array.
     */
    private function getAllFiles(): array
    {
        $files = [];
        foreach (new \RegexIterator(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->options['repo'])), '/(?<!vendor\/).*\.php/i') as $file) {
            $file = substr($file->getPathname(), (strlen($this->options['repo']) + 1));
            $files[$file] = [
                'status' => false,
                'line'   => null,
                'range'  => null,
                'change' => null,
            ];
        }

        $this->options['all'] = true;
        return $files;
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
        if (isset($this->options['all']) === true && empty($file) === false) {
            $files[$this->options['file']] = [];
        } else {
            $execString = sprintf(
                'git --git-dir="%s/.git" --work-tree="%s" %s %s --unified=0 %s | grep -E "^(@@|\+\+)"',
                $repo,
                $repo,
                $type,
                $commit,
                $file
            );

            $result  = shell_exec($execString);
            $lines   = preg_split('/\r\n|\r|\n/', $result);
            $crrFile = null;
            $skip    = false;
            foreach ($lines as $line) {
                $isFile = (substr($line, 0, 3) === '++ ' || substr($line, 0, 4) === '+++ ');
                if ($skip === true || $isFile === true) {
                    if (substr($line, -4) !== '.php') {
                        $skip = true;
                        continue;
                    } elseif ($isFile === true) {
                        $skip = false;
                    }

                    $crrFile         = substr($line, (strpos($line, ' b/') + 3));
                    $files[$crrFile] = [];
                } elseif ($skip === false && substr($line, 0, 3) === '@@ ' || substr($line, 0, 4) === '@@@ ') {
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
        $stndr      = $this->options['standard'];
        $php        = $this->options['php'];
        $phpcs      = $this->options['phpcs'];
        $warnings   = $this->options['warnings'];
        $phpVersion = (empty($this->options['php-version']) === true) ? '' : "--runtime-set php_version {$this->options['php-version']}";
        $execString = ($this->util->os->isWindows() === true) ? "$phpcs --report=json --standard=$stndr $file $warnings" :
            "$php $phpcs --report=json --standard=$stndr '$file' $warnings $phpVersion";

        $response = shell_exec($execString);
        // Some encoding issues makes PHPCS return empty object, causing invalid JSON.
        // This is a quick fix.
        $invalidJsons = [
            '},,,,{',
            '},,,{',
            '},,{',
        ];
        $json = json_decode(str_replace($invalidJsons, '},{', $response), true);
        
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

        if (isset($this->options['json']) === true && $this->options['json'] === true) {
            $this->json = ($this->json ?? []);
            $this->json[$file] = ($this->json[$file] ?? []);
            $this->json[$file][] = $error;
        } else {
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
        $this->validateOptions();

        $files    = (isset($this->options['fullscan']) === true) ? $this->getAllFiles() : $this->parseModifiedGitFiles();
        $checkAll = isset($this->options['all']);
        $checkSBC = 'Function closing brace must go on the next line';
        foreach ($files as $file => $gitChanges) {
            foreach ($this->parsePHPCSErrors("{$this->options['repo']}/$file") as $crrPhpcsError) {
                if ($checkAll === true) {
                    $this->printError($file, $crrPhpcsError);
                } else {
                    foreach ($gitChanges as $crrChange) {
                        if (
                            $crrPhpcsError['line'] >= $crrChange['line']
                            && $crrPhpcsError['line'] <= ($crrChange['line'] + $crrChange['range'])
                        ) {
                            $this->printError($file, $crrPhpcsError);
                        } elseif (
                            ($crrPhpcsError['line'] + 1) >= $crrChange['line']
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
            if (isset($this->options['json']) === true && $this->options['json'] === true) {
                echo json_encode($this->json);
            } else {
                echo PHP_EOL;
            }

            exit(1);
        } else {
            exit(0);
        }
    }


    /**
     * Set util class.
     *
     * @param Util $util Util class.
     *
     * @return void
     */
    public function setUtil(Util $util): void
    {
        $this->util = $util;
    }


    /**
     * Validate all required fieldsand exit if it fails.
     * TODO: split this method in a separated class.
     *
     * @return void
     */
    private function validateOptions(): void
    {
        // TODO: Move this validation to a separated class.
        $base = dirname(__DIR__);

        if (isset($this->options['help']) === true) {
            $this->util->printUsage();
        } elseif (isset($this->options['version']) === true) {
            $this->util->printVersion();
        }

        // First, we check for basic system requirements.
        if ($this->isPhar === true) {
            if (empty($this->options['phpcs']) === true) {
                $this->options['phpcs'] = \Phar::running(false) . ' --phpcs-wrapper';
            }
        } else {
            if (isset($this->options['phpcs']) === true) {
                $cwd          = getcwd();
                $pathRegular  = $this->options['phpcs'];
                $pathRelative = "$cwd/{$this->options['phpcs']}";
                $pathVendor   = "$base/vendor/squizlabs/php_codesniffer/bin/phpcs";
                if (empty(shell_exec("command -v $pathRegular")) === false) {
                    $this->options['phpcs'] = $pathRegular;
                } elseif (empty(shell_exec("command -v $pathRelative")) === false) {
                    $this->options['phpcs'] = $pathRelative;
                } elseif (file_exists($pathVendor) === true) {
                    $this->options['phpcs'] = $pathVendor;
                } else {
                    $this->exit("phpcs not found.\n\nPlease, make sure the given ``--path={$this->options['phpcs']}`` points to the correct path.");
                }
            } else {
                // It can be installed using composer, git+composer or downloaded.
                if (file_exists("$base/vendor/squizlabs/php_codesniffer/bin/phpcs") === true) {
                    $this->options['phpcs'] = "$base/vendor/squizlabs/php_codesniffer/bin/phpcs";
                } elseif (file_exists("$base/../../squizlabs/php_codesniffer/bin/phpcs") === true) {
                    $this->options['phpcs'] = "$base/../../squizlabs/php_codesniffer/bin/phpcs";
                } else {
                    $this->options['phpcs'] = 'phpcs';
                }

                $this->exitIf(
                    empty(shell_exec("command -v {$this->options['phpcs']}")),
                    "{$this->options['phpcs']} not valid phpcs command. Please, make sure it exists."
                );
            }
        }

        $this->exitIf(
            (version_compare(PHP_VERSION, '7.1') === -1),
            'super-giggle requires at leaset PHP 7.1. Your PHP version is ' . PHP_VERSION . ' :('
        );

        // Now, check for all options.
        if (isset($this->options['diff']) === true) {
            $this->options['type'] = 'diff';
        }

        if (isset($this->options['diff-cached']) === true) {
            $this->options['type'] = 'diff --cached';
        }

        if (isset($this->options['warnings']) === true) {
            $this->options['warnings'] = '--warning-severity=5';
        } else {
            $this->options['warnings'] = '--warning-severity=9';
        }

        $this->options['type']  = ($this->options['type'] ?? 'show');
        $this->options['php']   = ($this->options['php'] ?? 'php');
        $this->options['phpcs'] = ($this->options['phpcs'] ?? __DIR__ . '/../vendor/bin/phpcs');

        if (empty($this->options['repo']) === true && file_exists(getcwd() . '/.git') === true) {
            $this->options['repo'] = str_replace('\\', '/', realpath(getcwd()));
        } elseif (empty($this->options['repo']) === true) {
            if (isset($this->options['repo']) === true) {
                $this->exit('Empty value for ``--repo``');
            } else {
                if (
                    preg_match('#^(.+)\.git#i', shell_exec('git rev-parse --git-dir'), $result) === 1
                    && isset($result[1]) === true
                ) {
                    $this->options['repo'] = $result[1];
                } else {
                    $this->exit('Missing ``--repo``');
                }
            }
        }

        if (empty($this->options['standard']) === true) {
            if (file_exists("{$this->options['repo']}/phpcs.xml") === true) {
                $this->options['standard'] = "{$this->options['repo']}/phpcs.xml";
            } else {
                $this->options['standard'] = 'PSR12';
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
            } elseif ($this->options['type'] === 'diff' || $this->options['type'] === 'diff --cached') {
                $this->options['commit'] = ($this->options['commit'] ?? '');
            } else {
                $this->exit('Invalid value for --type.');
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
