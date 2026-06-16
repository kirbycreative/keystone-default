<?php

namespace Keystone\Toolkit\Properties;

use Keystone\Toolkit\Properties\Property;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Tags extends Property
{
    public $type = 'tags';

    public $fillable = true;

    public $label = 'Tags';

    //public $cast = 'array';

    public $rules = [
        'type' => 'array',
        'nullable',
    ];

    public function accessor($value)
    {
        return $value;
    }
}
