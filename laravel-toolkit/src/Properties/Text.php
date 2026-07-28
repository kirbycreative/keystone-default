<?php

namespace Keystone\Toolkit\Properties;

class Text extends Property
{
    public $type = 'text';

    public $fillable = true;

    public $label = 'Text';

    public $rules = [
        'type' => 'string',
        'required',
    ];

    public function table()
    {
        return [];
    }

    public function form()
    {
        return [];
    }
}
