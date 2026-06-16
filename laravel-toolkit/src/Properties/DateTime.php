<?php

namespace Keystone\Toolkit\Properties;

use Keystone\Toolkit\Properties\Property;
use Illuminate\Database\Eloquent\Casts\Attribute;

class DateTime extends Property
{
    public $type = 'datetime';

    public $fillable = true;

    public $label = 'Date';

    public $cast = 'datetime:Y-m-d H:i:s';

    public $rules = [
        'type' => 'datetime',
        'required',
    ];

    public function table()
    {
        return [
            'align' => 'center',
            'format' => function ($value, $instance = null) {
                return date('Y-m-d', strtotime($value));
            }
        ];
    }


    public function form()
    {
        return [
            'type' => 'date',
            'cast' => 'Y-m-d'
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
