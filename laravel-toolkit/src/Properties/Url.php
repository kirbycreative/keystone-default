<?php

namespace Keystone\Toolkit\Properties;

class Url extends Property
{
    public $type = 'url';

    public $fillable = true;

    public $label = 'Url';

    public $rules = [
        'type' => 'string',
        'required',
    ];
}
