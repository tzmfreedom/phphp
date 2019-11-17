<?php

require __DIR__ . '/../vendor/autoload.php';

use PHPHP\PHPInterpreter;
use PhpParser\Error;
use PhpParser\ParserFactory;

$file = $argv[1];
$lines = file($file);
if (preg_match('/\A#!.*/', $lines[0])) {
    array_shift($lines);
}
$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
try {
    $interpreter = new PHPInterpreter($parser);
    $interpreter->setDebug(true);
    $code = implode(PHP_EOL, $lines);
    $interpreter->run($code, $file);
} catch (Error $error) {
    echo "Parse error: {$error->getMessage()}\n";
    return;
}
