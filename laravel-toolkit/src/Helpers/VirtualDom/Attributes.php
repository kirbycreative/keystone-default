<?php

namespace Keystone\Toolkit\Helpers\VirtualDom;

use ArrayObject;

class Attributes extends ArrayObject
{

    function __construct($attributes = [])
    {
        parent::setFlags(parent::ARRAY_AS_PROPS);

        if (!empty($attributes)) {
            foreach ($attributes as $key => $val) {
                $this->{$key} = $val;
            }
        }
    }

    public function set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    public function get($key)
    {
        return $this->offsetGet($key);
    }

    public function has($key)
    {
        return $this[$key] ? true : false;
    }

    public function keys()
    {
        return array_keys($this->getArrayCopy());
    }

    public function toArray()
    {
        return $this->getArrayCopy();
    }
}
