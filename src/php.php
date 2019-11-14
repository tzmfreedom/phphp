<?php

require 'vendor/autoload.php';

use PhpParser\Error;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Expr;
use PhpParser\ParserFactory;
use PhpParser\NodeDumper;

function main(string $code)
{
    $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    try {
        $interpreter = new PhpInterpreter();
        $ast = $parser->parse($code);
        foreach ($ast as $stmt) {
            $interpreter->process($stmt);
        }
    $dumper = new NodeDumper;
    echo $dumper->dump($ast) . "\n";
    } catch (Error $error) {
        echo "Parse error: {$error->getMessage()}\n";
        return;
    }
}

class PhpInterpreter
{
    private $constMap;

    function __construct()
    {
        $this->constMap = [
            'PHP_EOL' => new \PhpParser\Node\Scalar\String_(PHP_EOL),
        ];
    }

    function process($node)
    {
        switch (get_class($node)) {
            case Stmt\Expression::class:
                return $this->process($node->expr);
            case Echo_::class:
                $returnNode = $this->process($node->exprs[0]);
                echo $returnNode->value;
                break;
            case Expr\FuncCall::class:
                switch ($node->name->toString()) {
                    case "var_dump":
                        $returnNode = $this->process($node->args[0]);
                        var_dump($returnNode->value);
                        break;
                }
                return;
            case \PhpParser\Node\Expr\BinaryOp\Concat::class:
                $left = $this->process($node->left);
                $right = $this->process($node->right);
                return new \PhpParser\Node\Scalar\String_($left->value . $right->value);
            case \PhpParser\Node\Expr\BinaryOp\Plus::class:
                $left = $this->process($node->left);
                $right = $this->process($node->right);
                return new \PhpParser\Node\Scalar\LNumber($left->value + $right->value);
            case \PhpParser\Node\Expr\BinaryOp\Minus::class:
                $left = $this->process($node->left);
                $right = $this->process($node->right);
                return new \PhpParser\Node\Scalar\LNumber($left->value - $right->value);
            case \PhpParser\Node\Expr\BinaryOp\Mul::class:
                $left = $this->process($node->left);
                $right = $this->process($node->right);
                return new \PhpParser\Node\Scalar\LNumber($left->value * $right->value);
            case \PhpParser\Node\Expr\BinaryOp\Div::class:
                $left = $this->process($node->left);
                $right = $this->process($node->right);
                return new \PhpParser\Node\Scalar\LNumber($left->value / $right->value);
            case \PhpParser\Node\Arg::class:
                return $this->process($node->value);
            case \PhpParser\Node\Expr\ConstFetch::class:
                return $this->constMap[$node->name->toString()];
            case \PhpParser\Node\Scalar\String_::class:
            case \PhpParser\Node\Scalar\DNumber::class:
            case \PhpParser\Node\Scalar\LNumber::class:
                return $node;
        }
    }
}
$code = file_get_contents("php://stdin");
main($code);

