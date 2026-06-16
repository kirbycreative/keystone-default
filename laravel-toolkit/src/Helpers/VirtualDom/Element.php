<?php

namespace Keystone\Toolkit\Helpers\VirtualDom;

use Keystone\Toolkit\Helpers\VirtualDom\Attributes;
use Keystone\Toolkit\Helpers\VirtualDom\Children;
use Keystone\Toolkit\Helpers\VirtualDom\Render;

function first($arr)
{
    return $arr[0];
}

class Element
{

    static function is($data)
    {
        if ((is_object($data) || is_array($data)) && data_get($data, 'tag')) return true;
        return false;
    }

    static function fromArray($arr)
    {
        return new Element($arr['tag'], $arr['attributes'] ?? [], $arr['children'] ?? []);
    }

    public $tag = 'div';
    public $attributes;
    public $children = [];
    public $ref;
    public $parent;

    function __construct($tag = 'div', $attributes = [], $children = [], $ref = null)
    {
        if (is_a($tag, 'Keystone\Toolkit\Helpers\VirtualDom\Element')) return $tag;
        $this->tag = $tag;
        $this->attributes = new Attributes($attributes);
        $this->children = is_string($children) ? [$children] : $children;
        if (!empty($ref)) $this->ref = $ref;
    }

    private function parseChild( /* Child Arguments */)
    {

        $num = func_num_args();
        $args = func_get_args();
        if ($num == 1 && is_a(first($args), 'Keystone\Toolkit\Helpers\VirtualDom\Element')) {
            return first($args);
        } else {
            return new Element(...func_get_args());
        }
    }

    public function addChild(/* Child Element */)
    {
        $child = $this->parseChild(...func_get_args());
        $child->parent = $this;
        $this->children[] = $child;
        return $child;
    }

    public function addTextChild(/* Child Element */)
    {
        $args = func_get_args();
        $this->children[] = $args[0];
    }

    public function prependChild(/* Child Element */)
    {
        $child = $this->parseChild(...func_get_args());
        $child->parent = $this;
        array_unshift($this->children, $child);
    }

    public function appendChild(/* Child Element */)
    {
        $child = $this->parseChild(...func_get_args());
        $child->parent = $this;
        $this->children[] = $child;
    }

    public function addText($text)
    {

        $this->children[] = $text;
    }

    public function toArray()
    {

        $arr = [
            'tag' => $this->tag,
            'attributes' => $this->attributes->toArray(),
            'children' => []
        ];

        foreach ($this->children as $child) {
            if (is_string($child))
                $arr['children'][] = $child;
            else
                $arr['children'][] = $child->toArray();
        }

        if (!empty($this->ref)) $arr->ref = $this->ref;
        return $arr;
    }

    public function toJson()
    {
        return json_encode($this->toArray(), true);
    }

    public function toHTML()
    {
        return Render::html($this);
    }

    public function render($part = null)
    {

        return Render::htmlPart($this, $part);
    }
}
