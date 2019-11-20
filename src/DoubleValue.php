<?php

namespace PHPHP;

class DoubleValue extends Value
{
    /**
     * @var bool
     */
    private $value;

    /**
     * DoubleValue constructor.
     * @param float $value
     */
    public function __construct(float $value)
    {
        $this->value = $value;
    }

    /**
     * @return double
     */
    public function getValue()
    {
        return $this->value;
    }
}
