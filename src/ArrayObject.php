<?php

namespace PHPHP;

use PhpParser\Node\Expr\ArrayItem;

class ArrayObject
{
    private $node;
    private $items;

    public function __construct($node)
    {
        $this->node = $node;
        $items = [];
        foreach ($node->items as $i => $item) {
            if (is_null($item->key)) {
                $items[$i] = $item;
            } else {
                $items[$item->key->value] = $item;
            }
        }
        $this->items = $items;
    }

    public function get($key)
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key]->value;
        }
        throw new \Exception("no index item");
    }

    public function set($key, $value)
    {
        $this->node->items[$key] = new ArrayItem($value);
    }
}
