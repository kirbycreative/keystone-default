<?php

namespace Keystone\Toolkit\Properties;

use Keystone\Toolkit\Properties\Property;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Description extends Property
{
    public $type = 'description';

    public $fillable = true;

    public $label = 'Description';


    public $rules = [
        'type' => 'string',
        'required',
    ];

    public function table()
    {
        return [
            'width' => [
                'min' => 250,
            ],
            'truncate' => 175,
            'align' => 'left'
        ];
    }
}
