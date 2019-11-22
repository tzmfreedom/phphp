<?php

namespace PHPHP;

class ReturnObject
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getValue($key)
    {
        return $this->value;
    }
}
