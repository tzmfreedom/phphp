<?php

namespace PHPHP;

abstract class Value
{
    /**
     * @return mixed
     */
    public abstract function getValue();

    public function toString(): string
    {
        return strval($this->getValue());
    }
}
