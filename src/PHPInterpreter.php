<?php

namespace PHPHP;

use PhpParser\Node\Scalar\MagicConst\Dir;
use PhpParser\Node\Scalar\MagicConst\File;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Expr;
use PhpParser\NodeDumper;
use PhpParser\Parser;

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

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var string
     */
    private $currentFilePath;

    /**
     * @var bool
     */
    private $debug;

    public function __construct(Parser $parser)
    {
        $this->constMap = [
            'PHP_EOL' => new \PhpParser\Node\Scalar\String_(PHP_EOL),
            'true' => new BoolValue(true),
            'false' => new BoolValue(false),
        ];
        $this->currentEnv = new VariableEnvironment(null);
        $this->parser = $parser;
    }

    /**
     * @param bool $debug
     */
    public function setDebug(bool $debug)
    {
        $this->debug = $debug;
    }

    /**
     * @param string $code
     * @param string $filePath
     * @throws \Exception
     */
    public function run(string $code, string $filePath)
    {
        $prevPath = $this->currentFilePath;
        $this->currentFilePath = realpath($filePath);
        $ast = $this->parser->parse($code);

        if ($this->debug) {
            $dumper = new NodeDumper;
            echo $dumper->dump($ast) . "\n";
        }

        foreach ($ast as $stmt) {
            $this->evaluate($stmt);
        }
        $this->currentFilePath = $prevPath;
    }

    /**
     * @param $node
     * @return mixed|InstanceValue|NullValue|\PhpParser\Node\Scalar\LNumber|String_|void
     * @throws \Exception
     */
    public function evaluate($node)
    {
        switch (get_class($node)) {
            case Stmt\Expression::class:
                return $this->evaluate($node->expr);
            case Echo_::class:
                $ret = $this->evaluate($node->exprs[0]);
                echo $ret->toString();
                break;
            case Expr\FuncCall::class:
                $name = $node->name->toString();
                switch ($name) {
                    case "var_dump":
                        $returnNode = $this->evaluate($node->args[0]);
                        var_dump($returnNode->getValue());
                        return;
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
                return new StringValue($left->toString() . $right->toString());
            case \PhpParser\Node\Expr\BinaryOp\Plus::class:
                $left = $this->evaluate($node->left);
                $right = $this->evaluate($node->right);
                return new LongValue($left->getValue() + $right->getValue());
            case \PhpParser\Node\Expr\BinaryOp\Minus::class:
                $left = $this->evaluate($node->left);
                $right = $this->evaluate($node->right);
                return new LongValue($left->getValue() - $right->getValue());
            case \PhpParser\Node\Expr\BinaryOp\Mul::class:
                $left = $this->evaluate($node->left);
                $right = $this->evaluate($node->right);
                return new LongValue($left->getValue() * $right->getValue());
            case \PhpParser\Node\Expr\BinaryOp\Div::class:
                $left = $this->evaluate($node->left);
                $right = $this->evaluate($node->right);
                return new LongValue($left->getValue() / $right->getValue());
            case Expr\BinaryOp\Smaller::class:
                $left = $this->evaluate($node->left);
                $right = $this->evaluate($node->right);
                return new BoolValue($left->getValue() < $right->getValue());
            case Expr\BinaryOp\SmallerOrEqual::class:
                $left = $this->evaluate($node->left);
                $right = $this->evaluate($node->right);
                return new BoolValue($left->getValue() <= $right->getValue());
            case Expr\BinaryOp\Greater::class:
                $left = $this->evaluate($node->left);
                $right = $this->evaluate($node->right);
                return new BoolValue($left->getValue() > $right->getValue());
            case Expr\BinaryOp\GreaterOrEqual::class:
                $left = $this->evaluate($node->left);
                $right = $this->evaluate($node->right);
                return new BoolValue($left->getValue() >= $right->getValue());
            case Expr\BinaryOp\Equal::class:
                $left = $this->evaluate($node->left);
                $right = $this->evaluate($node->right);
                return new BoolValue($left->getValue() == $right->getValue());
            case Expr\BinaryOp\NotEqual::class:
                $left = $this->evaluate($node->left);
                $right = $this->evaluate($node->right);
                return new BoolValue($left->getValue() != $right->getValue());
            case Expr\BinaryOp\Identical::class:
                $left = $this->evaluate($node->left);
                $right = $this->evaluate($node->right);
                return new BoolValue($left->getValue() === $right->getValue());
            case Expr\BinaryOp\NotIdentical::class:
                $left = $this->evaluate($node->left);
                $right = $this->evaluate($node->right);
                return new BoolValue($left->getValue() !== $right->getValue());
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
                return new StringValue($node->value);
            case \PhpParser\Node\Scalar\DNumber::class:
                return new DoubleValue($node->value);
            case \PhpParser\Node\Scalar\LNumber::class:
                return new LongValue($node->value);
            case Stmt\Return_::class:
                return $node;
            case Stmt\Function_::class:
                $name = $node->name->toString();
                $this->addFunction($name, $node->params, $node->stmts);
                return;
            case Expr\New_::class:
                $name = $node->class->toString();
                if (array_key_exists($name, $this->classMap)) {
                    $obj = new InstanceValue($this->classMap[$name]);
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
            case Expr\Include_::class:
                $file = $this->evaluate($node->expr);
                $code = file_get_contents($file->value);
                $this->run($code, $file->value);
                return;
            // TODO: impl __DIR__ and __FILE__
            case Dir::class:
                return new String_(dirname($this->currentFilePath));
            case File::class:
                return new String_($this->currentFilePath);
            case Stmt\If_::class:
                $cond = $this->evaluate($node->cond);
                if ($cond->value) {
                    foreach ($node->stmts as $stmt) {
                        $ret = $this->evaluate($stmt);
                        if ($ret instanceof Stmt\Return_) {
                            return $this->evaluate($ret->expr);
                        }
                    }
                    return;
                } else if (count($node->elseifs) > 0) {
                    foreach ($node->elseifs as $elseif) {
                        $cond = $this->evaluate($elseif->cond);
                        if ($cond->value) {
                            foreach ($elseif->stmts as $stmt) {
                                $ret = $this->evaluate($stmt);
                                if ($ret instanceof Stmt\Return_) {
                                    return $this->evaluate($ret->expr);
                                }
                            }
                            return;
                        }
                    }
                }
                if (!is_null($node->else)) {
                    foreach ($node->else->stmts as $stmt) {
                        $ret = $this->evaluate($stmt);
                        if ($ret instanceof Stmt\Return_) {
                            return $this->evaluate($ret->expr);
                        }
                    }
                }
                return;
            case Expr\Exit_::class:
                exit;
            case Stmt\For_::class:
                foreach ($node->init as $init) {
                    $this->evaluate($init);
                }
                while (true) {
                    $cond = $this->evaluate($node->cond[0]);
                    if (!$cond->getValue()) {
                        break;
                    }
                    foreach ($node->stmts as $stmt) {
                        $this->evaluate($stmt);
                    }
                    foreach ($node->loop as $loop) {
                        $this->evaluate($loop);
                    }
                }
                return;
            case Stmt\Foreach_::class:
                $arrayValue = $this->evaluate($node->expr);
                foreach ($arrayValue->getValue() as $key => $item) {
                    $this->currentEnv->set($node->valueVar->name, $item);
                    if (!is_null($node->keyVar)) {
                        $this->currentEnv->set($node->keyVar->name, $key);
                    }
                    foreach ($node->stmts as $stmt) {
                        $ret = $this->evaluate($stmt);
                        if ($ret instanceof Stmt\Return_) {
                            return $this->evaluate($ret->expr);
                        }
                        if ($ret instanceof Stmt\Break_) {
                            break;
                        }
                        if ($ret instanceof Stmt\Continue_) {
                            break;
                        }
                    }
                }
                return;
            case Expr\Array_::class:
                $items = [];
                foreach ($node->items as $i => $item) {
                    $value = $this->evaluate($item->value);
                    if (is_null($item->key)) {
                        $items[$i] = $value;
                    } else {
                        $items[$item->key->value] = $value;
                    }
                }
                return new ArrayValue($items);
            case Expr\ArrayDimFetch::class:
                $var = $this->evaluate($node->var);
                $dim = $this->evaluate($node->dim);
                return $var->get($dim->getValue());
            case Stmt\TryCatch::class:
                foreach ($node->stmts as $stmt) {
                    $ret = $this->evaluate($stmt);
                    if ($ret instanceof Stmt\Return_) {
                        return $this->evaluate($ret->expr);
                    }
                    if ($ret instanceof ThrowObject) {
                        foreach ($node->catches as $catch) {
                            foreach ($catch->types as $type) {
                                if ($ret->isEqual($type->toString())) {
                                    foreach ($catch->stmts as $stmt) {
                                        $ret = $this->evaluate($stmt);
                                        if ($ret instanceof Stmt\Return_) {
                                            return $this->evaluate($ret->expr);
                                        }
                                    }
                                    return; // TODO: impl
                                }
                            }
                        }
                    }
                    if (!is_null($node->finally)) {
                        foreach ($node->finally->stmts as $stmt) {
                            $ret = $this->evaluate($stmt);
                            if ($ret instanceof Stmt\Return_) {
                                return $this->evaluate($ret->expr);
                            }
                        }
                    }
                }
                return;
            case Expr\PreInc::class:
                $ret = new LongValue($this->evaluate($node->var)->getValue() + 1);
                $this->currentEnv->set($node->var->name, $ret);
                return $ret;
            case Expr\PostInc::class:
                $ret = $this->evaluate($node->var);
                $this->currentEnv->set($node->var->name, new LongValue($ret->getValue() + 1));
                return $ret;
            case Expr\PreDec::class:
                $ret = new Longvalue($this->evaluate($node->var)->getValue() - 1);
                $this->currentEnv->set($node->var->name, $ret);
                return $ret;
            case Expr\PostDec::class:
                $ret = $this->evaluate($node->var);
                $this->currentEnv->set($node->var->name, new LongValue($ret->getValue() - 1));
                return $ret;
        }
    }

    /**
     * @param string $name
     * @param array $params
     * @param array $stmts
     */
    private function addFunction(string $name, array $params, array $stmts): void
    {
        $this->functions[$name] = new FunctionObject($name, $params, $stmts);
    }

    /**
     * @param string $name
     * @return bool
     */
    private function isFunctionExists(string $name): bool
    {
        return array_key_exists($name, $this->functions);
    }

    /**
     * @param string $name
     * @return FunctionObject
     */
    private function getFunction(string $name): FunctionObject
    {
        return $this->functions[$name];
    }

    /**
     * @param Stmt\Class_ $node
     */
    private function addClass(Stmt\Class_ $node)
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
