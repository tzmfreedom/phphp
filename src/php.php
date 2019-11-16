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

class VariableEnvironment
{
    /**
     * @var VariableEnvironment
     */
    private $parent;

    /**
     * @var array
     */
    private $store;

    public function __construct(?VariableEnvironment $parent)
    {
        $this->parent = $parent;
        $this->store = [];
    }

    public function isExists(string $key)
    {
        if (array_key_exists($key, $this->store)) {
            return true;
        }
        if (is_null($this->parent)) {
            return false;
        }
        return $this->parent->isExists($key);
    }

    public function get(string $key)
    {
        if (array_key_exists($key, $this->store)) {
            return $this->store[$key];
        }
        if (is_null($this->parent)) {
            throw new Exception("no exist key"); // TODO: impl
        }
        return $this->parent->get($key);
    }

    public function set(string $key, $value)
    {
        if ($this->isExists($key)) {
            if (array_key_exists($key, $this->store)) {
                $this->store[$key] = $value;
                return;
            }
            $this->parent->set($key, $value);
            return;
        }
        $this->store[$key] = $value;
    }
}

class FunctionObject
{
    private $name;
    private $params;
    private $stmts;

    public function __construct(string $name, array $params, array $stmts)
    {
        $this->name = $name;
        $this->params = $params;
        $this->stmts = $stmts;
    }

    public function call(PhpInterpreter $interpreter)
    {
        foreach ($this->stmts as $stmt) {
            $interpreter->process($stmt);
        }
    }

    /**
     * @param int $index
     * @return \PhpParser\Node\Param[]
     */
    public function getParams(): array
    {
        return $this->params;
    }
}

class PhpInterpreter
{
    /**
     * @var array
     */
    private $constMap;

    /**
     * @var array
     */
    private $functions;

    /**
     * @var VariableEnvironment
     */
    private $currentEnv;

    function __construct()
    {
        $this->constMap = [
            'PHP_EOL' => new \PhpParser\Node\Scalar\String_(PHP_EOL),
        ];
        $this->currentEnv = new VariableEnvironment(null);
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
                $name = $node->name->toString();
                switch ($name) {
                    case "var_dump":
                        $returnNode = $this->process($node->args[0]);
                        var_dump($returnNode->value);
                        break;
                }
                if ($this->isFunctionExists($node->name->toString())) {
                    $function = $this->getFunction($name);
                    $args = [];
                    foreach ($node->args as $i => $arg) {
                        $args[$i] = $this->process($arg);
                    }
                    foreach ($function->getParams() as $i => $param) {
                        $this->currentEnv->set($param->var->toString(), $args[i]);
                    }
                    $prevEnv = $this->currentEnv;
                    $this->currentEnv = new VariableEnvironment(null);
                    $function->call($this, $node->args);
                    $this->currentEnv = $prevEnv;
                }
                return;
            case \PhpParser\Node\Expr\Assign::class:
                $expr = $this->process($node->expr);
                $this->currentEnv->set($node->var->name, $expr);
                return $expr;
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
            case \PhpParser\Node\Expr\Variable::class:
                if ($this->currentEnv->isExists($node->name)) {
                    return $this->currentEnv->get($node->name);
                }
                return null;
            case \PhpParser\Node\Scalar\String_::class:
            case \PhpParser\Node\Scalar\DNumber::class:
            case \PhpParser\Node\Scalar\LNumber::class:
                return $node;
            case Stmt\Function_::class:
                $name = $node->name->toString();
                $this->addFunction($name, $node->params, $node->stmts);
        }
    }

    public function addFunction(string $name, array $params, array $stmts): void
    {
        $this->functions[$name] = new FunctionObject($name, $params, $stmts);
    }

    public function isFunctionExists(string $name): bool
    {
        return array_key_exists($name, $this->functions);
    }

    public function getFunction(string $name): FunctionObject
    {
        return $this->functions[$name];
    }
}
$code = file_get_contents("php://stdin");
main($code);

