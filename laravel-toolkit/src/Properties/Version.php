<?php

namespace Keystone\Toolkit\Properties;

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

    // public function accessor() {}
}
