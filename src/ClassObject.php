<?php

namespace PHPHP;

class ClassObject
{
    private $name;
    public $methods;
    public $extend;
    public $implements;
    public $properties;

    public function __construct(string $name, array $methods, ClassObject $extend = null, array $implements = [])
    {
        $this->name = $name;
        $this->extend = $extend;
        $this->implements = $implements;
        $this->methods = $methods;
    }

    public function getMethod(string $name, bool $allowProtected, bool $allowPrivate)
    {
        if (array_key_exists($name, $this->methods)) {
            $method = $this->methods[$name];
            if (
                $method->isPublic() ||
                ($allowProtected && $method->isProtected()) ||
                ($allowPrivate && $method->isPrivate())
            ) {
                return $method;
            }
            throw new Exception('no method exists');
        }
        return $this->extend->getMethod($name, $allowProtected, false);
    }

    public function getProperty(string $name, bool $allowProtected, bool $allowPrivate)
    {
        if (array_key_exists($name, $this->properties)) {
            $property = $this->properties[$name];
            if (
                $property->isPublic() ||
                ($allowProtected && $property->isProtected()) ||
                ($allowPrivate && $property->isPrivate())
            ) {
                return $property;
            }
            return null;
        }
        return $this->extend->getProperty($name, $allowProtected, false);
    }
}
