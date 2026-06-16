<?php

namespace Keystone\Toolkit\Properties;

use Keystone\Toolkit\Properties\Property;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Image extends Property
{
    public $type = 'image';

    public $fillable = true;

    public $label = 'Image';


    public $rules = [
        'type' => 'string',
        'required',
    ];

    public function table()
    {
        return [
            'accessor' => function ($value) {
                return '<img width="100" src="' . $value . '" />';
            }
        ];
    }

    public function form()
    {
        return [
            'type' => 'file',

        ];
    }
}
