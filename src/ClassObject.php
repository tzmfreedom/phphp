<?php

namespace PHPHP;

class ClassObject
{
    private $name;
    public $methods;
    public $extend;
    public $implements;

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
}
