<?php

namespace Keystone\Toolkit\Properties;

use Keystone\Toolkit\Properties\Property;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Json extends Property
{
    public $type = 'json';

    public $fillable = true;

    public $label = 'Json';

    public $cast = 'array';

    public $rules = [
        'type' => 'json'
    ];

    public function table()
    {
        return [
            'wrap' => 'nowrap',
        ];
    }

    //public function accessor() {}
}
