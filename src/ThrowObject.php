<?php

namespace PHPHP;

use PhpParser\Node\Stmt;

class ThrowObject
{
    private $expr;

    public function __construct($expr)
    {
        $this->expr = $expr;
    }

    public function isEqual($class)
    {
        return get_class($this->expr) === $class;
    }
}
