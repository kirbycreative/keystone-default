<?php

namespace Keystone\Toolkit\Properties;

class Hidden extends Property
{
    public $type = 'hidden';

    public $fillable = true;

    public $label = 'Hidden';


    public $rules = [
        'type' => 'int',
        'required',
    ];

    public function table()
    {
        return false;
    }

    public function form()
    {
        return [
            'type' => 'hidden',

        ];
    }
}
