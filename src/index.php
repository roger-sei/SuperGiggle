<?php
/**
 * SupperGiggler for coding standards validation against a given commit
 *
 * @author  Roger Sei <roger.sei@icloud.com>
 * @license //github.com/roger-sei/SuperGiggle/blob/master/LICENSE BSD Licence
 */

require_once __DIR__.'/../vendor/autoload.php';

$phpcs = new SupperGiggle\Runner();
$phpcs->autoRun($argv);


