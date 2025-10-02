<?php

error_reporting(E_ALL | E_DEPRECATED | E_USER_DEPRECATED);
ini_set('display_errors', '1');

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if ($errno === E_USER_DEPRECATED || $errno === E_DEPRECATED) {
        echo "[GLOBAL DEPRECATION] $errstr in $errfile:$errline\n";
    }

    return false; // Let PHPUnit also handle it
});


// Run PHPUnit and capture output
$output = [];
$returnCode = 0;
exec("vendor/bin/phpunit --configuration phpunit.xml --fail-on-deprecation", $output, $returnCode);

// Print each line of output
foreach ($output as $line) {
    echo $line . PHP_EOL;
}
