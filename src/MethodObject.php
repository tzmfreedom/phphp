<?php

namespace PHPHP;

use PhpParser\Node\Stmt;

class MethodObject
{
    public $method;

    public function __construct(Stmt\ClassMethod $method)
    {
        $this->method = $method;
    }

    public function call(PhpInterpreter $interpreter)
    {
        foreach ($this->method->stmts as $stmt) {
            $ret = $interpreter->evaluate($stmt);
            if ($ret instanceof Stmt\Return_) {
                return $interpreter->evaluate($ret->expr);
            }
        }
        return new NullValue();
    }

    /**
     * @return \PhpParser\Node\Param[]
     */
    public function getParams(): array
    {
        return $this->method->params;
    }

    public function isPublic()
    {
        return $this->method->flags & Stmt\Class_::MODIFIER_PUBLIC;
    }

    public function isPrivate()
    {
        return $this->method->flags & Stmt\Class_::MODIFIER_PRIVATE;
    }

    public function isProtected()
    {
        return $this->method->flags & Stmt\Class_::MODIFIER_PROTECTED;
    }
}
