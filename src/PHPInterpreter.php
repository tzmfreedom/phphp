<?php

namespace PHPHP;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Expr;

class PHPInterpreter
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

    /**
     * @var array
     */
    private $classMap;

    function __construct()
    {
        $this->constMap = [
            'PHP_EOL' => new \PhpParser\Node\Scalar\String_(PHP_EOL),
            'true' => new BoolObject(true),
            'false' => new BoolObject(false),
        ];
        $this->currentEnv = new VariableEnvironment(null);
    }

    function evaluate($node)
    {
        switch (get_class($node)) {
            case Stmt\Expression::class:
                return $this->evaluate($node->expr);
            case Echo_::class:
                $returnNode = $this->evaluate($node->exprs[0]);
                echo $returnNode->value;
                break;
            case Expr\FuncCall::class:
                $name = $node->name->toString();
                switch ($name) {
                    case "var_dump":
                        $returnNode = $this->evaluate($node->args[0]);
                        var_dump($returnNode->value);
                        break;
                }
                if ($this->isFunctionExists($node->name->toString())) {
                    $function = $this->getFunction($name);
                    $args = [];
                    foreach ($node->args as $i => $arg) {
                        $args[$i] = $this->evaluate($arg);
                    }
                    $prevEnv = $this->currentEnv;
                    $this->currentEnv = new VariableEnvironment(null);
                    foreach ($function->getParams() as $i => $param) {
                        $this->currentEnv->set($param->var->name, $args[$i]);
                    };
                    $ret = $function->call($this);
                    $this->currentEnv = $prevEnv;
                    return $ret;
                }
                throw new \Exception("no function exists");
            case \PhpParser\Node\Expr\Assign::class:
                $expr = $this->evaluate($node->expr);
                $this->currentEnv->set($node->var->name, $expr);
                return $expr;
            case \PhpParser\Node\Expr\BinaryOp\Concat::class:
                $left = $this->evaluate($node->left);
                $right = $this->evaluate($node->right);
                return new \PhpParser\Node\Scalar\String_($left->value . $right->value);
            case \PhpParser\Node\Expr\BinaryOp\Plus::class:
                $left = $this->evaluate($node->left);
                $right = $this->evaluate($node->right);
                return new \PhpParser\Node\Scalar\LNumber($left->value + $right->value);
            case \PhpParser\Node\Expr\BinaryOp\Minus::class:
                $left = $this->evaluate($node->left);
                $right = $this->evaluate($node->right);
                return new \PhpParser\Node\Scalar\LNumber($left->value - $right->value);
            case \PhpParser\Node\Expr\BinaryOp\Mul::class:
                $left = $this->evaluate($node->left);
                $right = $this->evaluate($node->right);
                return new \PhpParser\Node\Scalar\LNumber($left->value * $right->value);
            case \PhpParser\Node\Expr\BinaryOp\Div::class:
                $left = $this->evaluate($node->left);
                $right = $this->evaluate($node->right);
                return new \PhpParser\Node\Scalar\LNumber($left->value / $right->value);
            case \PhpParser\Node\Arg::class:
                return $this->evaluate($node->value);
            case \PhpParser\Node\Expr\ConstFetch::class:
                return $this->constMap[$node->name->toString()];
            case \PhpParser\Node\Expr\Variable::class:
                if ($this->currentEnv->isExists($node->name)) {
                    return $this->currentEnv->get($node->name);
                }
                return new NullValue();
            case \PhpParser\Node\Scalar\String_::class:
            case \PhpParser\Node\Scalar\DNumber::class:
            case \PhpParser\Node\Scalar\LNumber::class:
            case Stmt\Return_::class:
                return $node;
            case Stmt\Function_::class:
                $name = $node->name->toString();
                $this->addFunction($name, $node->params, $node->stmts);
                return;
            case Expr\New_::class:
                $name = $node->class->toString();
                if (array_key_exists($name, $this->classMap)) {
                    $obj = new Instance($this->classMap[$name]);
                    return $obj;
                }
                throw new \Exception("no class exists");
            case Expr\MethodCall::class:
                $receiver = $this->evaluate($node->var);
                $isReceiverThis= $node->var instanceof \PhpParser\Node\Expr\Variable && $node->var->name === 'this';
                /** @var $method MethodObject */
                $method = $receiver->class->getMethod($node->name->toString(), $isReceiverThis, $isReceiverThis);
                $args = [];
                foreach ($node->args as $i => $arg) {
                    $args[$i] = $this->evaluate($arg);
                }
                if (count($args) < count($method->getParams())) {
                    throw new \Exception('few parameter');
                }
                $prevEnv = $this->currentEnv;
                $this->currentEnv = new VariableEnvironment(null);
                $this->currentEnv->set('this', $receiver);
                foreach ($method->getParams() as $i => $param) {
                    $this->currentEnv->set($param->var->name, $args[$i]);
                }
                $ret = $method->call($this);
                $this->currentEnv = $prevEnv;
                return $ret;
            case Stmt\Class_::class:
                $this->addClass($node);
                return;
            case Stmt\InlineHTML::class:
                echo $node->value;
                return;
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

    public function addClass(Stmt\Class_ $node)
    {
        $methods = [];
        foreach ($node->getMethods() as $method) {
            $methods[$method->name->toString()] = new MethodObject($method);
        }
        if ($node->extends) {
            $extends = $this->classMap[$node->extends->toString()];
        } else {
            $extends = null;
        }
        $this->classMap[$node->name->toString()] = new ClassObject($node->name, $methods, $extends, $node->implements);
    }
}
