<?php

namespace PHPHP;

class BoolObject
{
    /**
     * @var bool
     */
    public $value;

    public function __construct(bool $value)
    {
        $this->value = $value;
    }
}
