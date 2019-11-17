<?php

require __DIR__ . '/../vendor/autoload.php';

use PHPHP\PHPInterpreter;
use PhpParser\Error;
use PhpParser\ParserFactory;

function main(string $code)
{
    $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    try {
        $interpreter = new PHPInterpreter();
        $ast = $parser->parse($code);
//        $dumper = new NodeDumper;
//        echo $dumper->dump($ast) . "\n";

        foreach ($ast as $stmt) {
            $interpreter->evaluate($stmt);
        }
    } catch (Error $error) {
        echo "Parse error: {$error->getMessage()}\n";
        return;
    }
}

$code = file_get_contents("php://stdin");
main($code);
