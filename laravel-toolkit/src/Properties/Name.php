<?php

namespace Keystone\Toolkit\Properties;

use Keystone\Toolkit\Properties\Property;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Name extends Property
{
    public $type = 'string';

    public $fillable = true;

    public $label = 'Name';

    public $rules = [
        'alpha',
        'required',
    ];

    public function table()
    {
        return [
            'align' => 'center'
        ];
    }


    public function form()
    {
        return [
            'type' => 'text'
        ];
    }



    /*
    public function accessor(string $value): string
    {
        return ucfirst($value);
    }*/

    /*
    public function mutator(string $value): string
    {
        return ucfirst($value);
    }*/
}
