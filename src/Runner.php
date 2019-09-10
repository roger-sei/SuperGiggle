<?php

namespace SupperGiggle;

class Runner
{
    private $filesMatched = [];
    private $separator = PHP_EOL;
    private $options = [];

    public function __construct()
    {
        $this->separator = str_repeat('-', 110) . PHP_EOL;
    }
    public function autoRun(array $args): void
    {
        next($args);
        $options = [];
        $solos   = [];
        for ($arg=current($args); $arg; $arg=next($args)) {
            if (substr($arg, 0, 2) === '--' && strlen($arg) > 2) {
                if (strpos($arg, '=') !== false) {
                    preg_match('#--([\\w\\s]+)=("|\')?(.+)(\\2)?#', $arg, $results);
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
    protected function match(string $text, string $regex): string
    {
        preg_match("#$regex#", $text, $result);
        return $result[1] ?? $result[0] ?? '';
    }
    private function parseModifiedGitFiles(): array
    {
        $result  = shell_exec("git --git-dir={$this->options['repo']}/.git --work-tree={$this->options['repo']} show {$this->options['commit']} --unified=0 | egrep '^(@@|\+\+\+)'");
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
        $checkAll = isset($this->options['full-check']);
        foreach ($files as $file=>$gitErrors) {
            foreach ($this->parsePHPCSErrors("{$this->options['repo']}/$file") as $crrPhpcsError) {
                foreach ($gitErrors as $crrGitError) {
                    if ($checkAll
                        || ($crrPhpcsError['line'] >= $crrGitError['line'] && $crrPhpcsError['line'] <= ($crrGitError['line'] + $crrGitError['range']))
                    ) {
                        $this->printError($file, $crrPhpcsError);
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
    private function validateOptions(): void
    {
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
            if (isset($this->options['repo']) === true) {
                $result = shell_exec("git --git-dir={$this->options['repo']}/.git --work-tree={$this->options['repo']} log --oneline --color | head -n 10");
                $this->throw("Missing --commit.\nPlease, choose a commit or use --diff option to validate against the last change:\n$result");
            }
            
            $this->throw('Missing --commit.');
        }
    }
}
