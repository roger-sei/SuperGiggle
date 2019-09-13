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

class Runner
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
     * Possible options for reporting.
     *
     * @var array
     */
    private $reportOptions = [
        ''
    ];


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
                        $this->throw($message);
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

        foreach ($solos as &$arg) {
            if ($this->match($arg, '^[a-f0-9]{7}$') === $arg) {
                $options['commit'] = $arg;
            }
        }

        $this->options = $options;
        $this->run();
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
        $r = $this->options['repo'];
        $t = $this->options['check-type'];
        $c = $this->options['commit'];
        $f = $this->options['file'];

        $result  = shell_exec("git --git-dir=$r/.git --work-tree=$r $t $c --unified=0 $f | egrep '^(@@|\+\+\+)'");
        $lines   = explode(PHP_EOL, $result);
        $crrFile = null;
        $files   = [];
        foreach ($lines as $line) {
            if (substr($line, 0, 4) === '+++ ') {
                $crrFile         = substr($line, (strpos($line, ' b/') + 3));
                $files[$crrFile] = [];
            } elseif (substr($line, 0, 3) === '@@ ') {
                preg_match('/\+(\d+)\,(\d+)/', $line, $numbers);
                if (isset($numbers[2]) === true) {
                    $files[$crrFile][] = [
                        'status' => false,
                        'line'   => $numbers[1],
                        'range'  => $numbers[2],
                    ];
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
        $dir   = dirname(__FILE__);
        $stndr = $this->options['standard'];
        $php   = $this->options['php'];

        $response = shell_exec("$php $dir/../vendor/bin/phpcs --report=json --standard=$stndr '$file'");
        $json     = json_decode($response, true);
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
        if (isset($this->filesMatched[$file]) === false) {
            $this->filesMatched[$file] = true;
            echo "\nFILE: $file\n";
            echo $this->separator;
            echo '';
        }

        if (isset($this->options['verbose']) === true) {
            if (strlen($error['source']) > 60) {
                $verbose = ' | ' . substr($error['source'], 0, 57) . '...';
            } else {
                $verbose = ' | ' . str_pad($error['source'], 60, ' ', STR_PAD_RIGHT);
            }
        } else {
            $verbose = '';
        }

        echo str_pad($error['line'], 7, ' ', STR_PAD_LEFT);
        echo ' |' . str_pad($error['column'], 5, ' ', STR_PAD_LEFT);
        echo $verbose;
        echo ' | ' . $error['message'] . PHP_EOL;
    }


    /**
     * Print help information, in cli format
     *
     * @return void
     */
    public function printHelp(): void
    {
        echo "Please, be patiente. I'm building this XD\n";
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

        $this->validateOptions();

        $files    = $this->parseModifiedGitFiles();
        $checkAll = isset($this->options['all']);
        $checkSBC = 'Function closing brace must go on the next line';
        foreach ($files as $file => $gitErrors) {
            foreach ($this->parsePHPCSErrors("{$this->options['repo']}/$file") as $crrPhpcsError) {
                foreach ($gitErrors as $crrGitError) {
                    if ($checkAll === true) {
                        $this->printError($file, $crrPhpcsError);
                        break;
                    } elseif ($crrPhpcsError['line'] >= $crrGitError['line']
                        && $crrPhpcsError['line'] <= ($crrGitError['line'] + $crrGitError['range'])
                    ) {
                        $this->printError($file, $crrPhpcsError);
                    } elseif (($crrPhpcsError['line'] + 1) >= $crrGitError['line']
                        && $crrPhpcsError['line'] <= ($crrGitError['line'] + $crrGitError['range'])
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


    /**
     * Helper to display a message and exit.
     *
     * @param string $message Error message.
     *
     * @return void
     */
    private function throw(string $message): void
    {
        $error   = [];
        $error[] = $message;
        $error[] = 'Try --help for more information.';
        echo(join(PHP_EOL, $error));
        echo PHP_EOL;
        exit(-1);
    }


    /**
     * Validate all required fieldsand exit if it fails.
     *
     * @return void
     */
    private function validateOptions(): void
    {
        $this->options['check-type'] = ($this->options['check-type'] ?? 'show');
        $this->options['php']        = ($this->options['php'] ?? 'php');
        $this->options['standard']   = ($this->options['standard'] ?? 'PSR12');

        if (empty($this->options['repo']) === true) {
            if (isset($this->options['repo']) === true) {
                $this->throw('Empty value for "--repo"');
            } else {
                $this->throw('Missing "--repo"');
            }
        } elseif (file_exists($this->options['repo']) === false) {
            $this->throw("Directory \"{$this->options['repo']}\" not found");
        }

        if (empty($this->options['commit']) === true && empty($this->options['file']) === true) {
            if ($this->options['check-type'] === 'show') {
                if (isset($this->options['repo']) === true) {
                    $repo   = $this->options['repo'];
                    $arg    = "git --git-dir=$repo/.git --work-tree=$repo log --oneline --color | head -n 10";
                    $result = shell_exec($arg);
                    $error  = "Missing --commit.\nPlease, choose a commit ";
                    $error .= "or use --diff option to validate against the last change:\n$result";
                    $this->throw($error);
                }
            } elseif ($this->options['check-type'] === 'diff') {
                $this->options['commit'] = ($this->options['commit'] ?? '');
            } else {
                $this->thrown('Invalid value for --check-type.');
            }
        } else {
            $this->options['commit'] = ($this->options['commit'] ?? '');
        }

        $this->options['file'] = ($this->options['file'] ?? '');
        if (empty($this->options['file']) === false && file_exists($this->options['file']) === false) {
            $this->throw("File '{$this->options['file']}' doesn't appear to exist!");
        }
    }


}
