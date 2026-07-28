<?php

namespace Keystone\Toolkit\Helpers\VirtualDom;

use ArrayObject;

class Children extends ArrayObject
{
    public function __construct($attributes = [])
    {
        parent::setFlags(parent::ARRAY_AS_PROPS);
    }

    public function push() {}
}
