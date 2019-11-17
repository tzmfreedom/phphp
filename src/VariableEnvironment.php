<?php

namespace PHPHP;

class VariableEnvironment
{
    /**
     * @var VariableEnvironment
     */
    private $parent;

    /**
     * @var array
     */
    private $store;

    public function __construct(?VariableEnvironment $parent)
    {
        $this->parent = $parent;
        $this->store = [];
    }

    public function isExists(string $key)
    {
        if (array_key_exists($key, $this->store)) {
            return true;
        }
        if (is_null($this->parent)) {
            return false;
        }
        return $this->parent->isExists($key);
    }

    public function get(string $key)
    {
        if (array_key_exists($key, $this->store)) {
            return $this->store[$key];
        }
        if (is_null($this->parent)) {
            throw new Exception("no exist key"); // TODO: impl
        }
        return $this->parent->get($key);
    }

    public function set(string $key, $value)
    {
        if ($this->isExists($key)) {
            if (array_key_exists($key, $this->store)) {
                $this->store[$key] = $value;
                return;
            }
            $this->parent->set($key, $value);
            return;
        }
        $this->store[$key] = $value;
    }
}
