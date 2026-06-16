<?php

namespace Keystone\Toolkit\Properties;

use Keystone\Toolkit\Properties\Property;
use Illuminate\Database\Eloquent\Casts\Attribute;

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
