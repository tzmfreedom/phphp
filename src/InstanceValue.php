<?php

namespace PHPHP;

class InstanceValue
{
    private $class;
    private $properties;

    public function __construct(ClassObject $class)
    {
        $this->class = $class;
        $this->properties = [];
    }

    public function getClass()
    {
        return $this->class;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function getProperty(string $name)
    {
        return $this->properties[$name];
    }

    public function setProperty(string $name, $value)
    {
        $this->properties[$name] = $value;
    }
}
