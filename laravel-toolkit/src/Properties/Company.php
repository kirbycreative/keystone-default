<?php

namespace Keystone\Toolkit\Properties;

use Keystone\Toolkit\Properties\Property;
use Illuminate\Database\Eloquent\Casts\Attribute;

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
