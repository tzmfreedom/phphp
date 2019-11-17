<?php

namespace PHPHP;

use PhpParser\Node\Stmt;

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
            $ret = $interpreter->evaluate($stmt);
            if ($ret instanceof Stmt\Return_) {
                return $interpreter->evaluate($ret->expr);
            }
        }
        return new NullValue();
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
