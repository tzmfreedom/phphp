<?php

namespace PHPHP;

class BoolValue extends Value
{
    /**
     * @var bool
     */
    private $value;

    /**
     * BoolValue constructor.
     * @param bool $value
     */
    public function __construct(bool $value)
    {
        $this->value = $value;
    }

    /**
     * @return bool
     */
    public function getValue()
    {
        return $this->value;
    }
}
