<?php

namespace SupperGiggle;

/**
 * Núcleo do pacote GT8 framework.
 *
 * PHP Version 7
 *
 * @category  PHP
 * @package   GT8
 * @author    GT8 <contato@gt8.com.br>
 * @copyright 2009-2019 GT8
 * @license   //github.com/gt8/php-g-core/license GPL2
 * @version   Release: GIT: 1
 * @link      //github.com/gt8/php-g-core/
 */
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
     * @param array @args Arguments in CLI format
     *
     * @return void
     */
    public function autoRun(array $args): void
    {
        next($args);
        $options = [];
        $solos   = [];
        for ($arg=current($args); $arg; $arg=next($args)) {
            if (substr($arg, 0, 2) === '--' && strlen($arg) > 2) {
                if (strpos($arg, '=') !== false) {
                    preg_match('#--([\\w\\s\-]+)=("|\')?(.+)(\\2)?#', $arg, $results);
                    if (isset($results[2]) === true) {
                        $options[$results[1]] = $results[3];
                    } else {
                        $this->throw("Malformed argument \"$arg\". Check your sintax and try again or make a pull request to fix any error :P");
                    }
                } else {
                    $options[substr($arg, 2)] = next($args);
                }
            } else {
                $solos[] = $arg;
            }
        }

        foreach ($solos as $arg) {
            if ($this->match($arg, '^[a-f0-9]{7}$') === $arg) {
                $options['commit'] = $arg;
            }
        }

        $options['php']      = ($options['php'] ?? 'php');
        $options['standard'] = ($options['standard'] ?? 'PSR12');

        $this->options = $options;
        $this->run();
    }


    /**
     * Helper to match a given string using regex.
     *
     * @param string $text  The input string
     * @param string $regex The pattern to search for
     *
     * @return string The first string captured.
     */
    protected function match(string $text, string $regex): string
    {
        preg_match("#$regex#", $text, $result);
        return $result[1] ?? $result[0] ?? '';
    }


    /**
     * Performs a git show and parse the results.
     *
     * @return array The parsed results in a bidimensional array.
     */
    private function parseModifiedGitFiles(): array
    {
        $result  = shell_exec("git --git-dir={$this->options['repo']}/.git --work-tree={$this->options['repo']} {$this->options['check-type']} {$this->options['commit']} --unified=0 | egrep '^(@@|\+\+\+)'");
        $lines   = explode(PHP_EOL, $result);
        $crrFile = null;
        $files   = [];
        foreach ($lines as $line) {
            if (substr($line, 0, 4) === '+++ ') {
                $crrFile = substr($line, strpos($line, ' b/') + 3);
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


    private function parsePHPCSErrors(string $path): array
    {
        $dir      = dirname(__FILE__);
        $response = shell_exec("{$this->options['php']} $dir/../vendor/bin/phpcs --report=json --standard={$this->options['standard']} '$path'");
        $json     = json_decode($response, true);
        if (empty($json['files']) === false) {
            return current($json['files'])['messages'];
        } else {
            return [];
        }
    }
    private function printError(string $file, array $error): void
    {
        if (isset($this->filesMatched[$file]) === false) {
            $this->filesMatched[$file] = true;
            echo "\nFILE: $file\n";
            echo $this->separator;
            echo "";
        }

        echo str_pad($error['line'], 7, ' ', STR_PAD_LEFT) .' |'. str_pad($error['column'], 5, ' ', STR_PAD_LEFT) .' | '. $error['message']. PHP_EOL;
    }
    public function printHelp(): void
    {
        echo "Please, be patiente. I'm building this XD\n";
        exit(0);
    }
    public function run(array $options=null): void
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
    private function throw(string $message): void
    {
        $error = [];
        $error[] = $message;
        $error[] = "Try --help for more information.";
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

        if (empty($this->options['repo']) === true) {
            if (isset($this->options['repo']) === true) {
                $this->throw('Empty value for "--repo"');
            } else {
                $this->throw('Missing "--repo"');
            }
        } else if (file_exists($this->options['repo']) === false) {
            $this->throw("Directory \"{$this->options['repo']}\" not found");
        }

        if (empty($this->options['commit']) === true) {
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
                $this->thrown("Invalid value for --check-type.");
            }
        }

    }
}
