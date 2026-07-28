<?php

namespace Keystone\Toolkit\Properties;

class Slug extends Property
{
    public $type = 'slug';

    public $fillable = true;

    public $label = 'Slug';

    public $rules = [
        'type' => 'string',
        'required',
    ];

    public function table()
    {
        return [
            'fit' => true,
            'wrap' => 'nowrap',
        ];
    }

    public function form()
    {
        return [
            'type' => 'text',
        ];
    }

    // public function accessor() {}
}
