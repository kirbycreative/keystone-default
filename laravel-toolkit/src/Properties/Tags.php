<?php

namespace Keystone\Toolkit\Properties;

class Tags extends Property
{
    public $type = 'tags';

    public $fillable = true;

    public $label = 'Tags';

    // public $cast = 'array';

    public $rules = [
        'type' => 'array',
        'nullable',
    ];

    public function accessor($value)
    {
        return $value;
    }
}
