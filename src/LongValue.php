<?php

namespace PHPHP;

class LongValue extends Value
{
    /**
     * @var int
     */
    private $value;

    /**
     * LongValue constructor.
     * @param int $value
     */
    public function __construct(int $value)
    {
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }
}
