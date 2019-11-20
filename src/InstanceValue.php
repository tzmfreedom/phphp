<?php

namespace PHPHP;

class InstanceValue
{
    public $class;
    public $properties;

    public function __construct(ClassObject $class)
    {
        $this->class = $class;
        $this->properties = [];
    }
}
