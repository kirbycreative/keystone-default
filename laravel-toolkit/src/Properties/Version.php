<?php

namespace Keystone\Toolkit\Properties;

use Keystone\Toolkit\Properties\Property;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Version extends Property
{
    public $type = 'version';

    public $fillable = true;

    public $label = 'Version';

    public $prepend = 'v';

    public $rules = [
        'type' => 'float',
        'required',
    ];

    public function table()
    {
        return [
            'fit' => true,
            'wrap' => 'nowrap',
        ];
    }

    //public function accessor() {}
}
