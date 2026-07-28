<?php

namespace Keystone\Toolkit\Properties;

class Json extends Property
{
    public $type = 'json';

    public $fillable = true;

    public $label = 'Json';

    public $cast = 'array';

    public $rules = [
        'type' => 'json',
    ];

    public function table()
    {
        return [
            'wrap' => 'nowrap',
        ];
    }

    // public function accessor() {}
}
