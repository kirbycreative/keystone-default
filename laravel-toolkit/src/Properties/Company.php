<?php

namespace Keystone\Toolkit\Properties;

class Company extends Property
{
    public $type = 'company';

    public $fillable = true;

    public $label = 'Company';

    public $rules = [
        'type' => 'string',
        'required',
    ];
}
