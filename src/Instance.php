<?php

namespace PHPHP;

class Instance
{
    public $class;
    public $properties;

    public function __construct(ClassObject $class)
    {
        $this->class = $class;
        $this->properties = [];
    }
}
