<?php

namespace PHPHP;

class ArrayValue extends Value
{
    private $items;

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function get($key)
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }
        throw new \Exception("no index item");
    }

    public function set($key, $value)
    {
        $this->items[$key] = $value;
    }

    public function getValue()
    {
        return $this->items;
    }
}
