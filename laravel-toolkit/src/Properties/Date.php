<?php

namespace Keystone\Toolkit\Properties;

use Keystone\Toolkit\Properties\Property;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Date extends Property
{
    public $type = 'date';

    public $fillable = true;

    public $label = 'Date';

    public $cast = 'datetime:Y-m-d';

    public $rules = [
        'type' => 'date',
        'required',
    ];

    public function table()
    {
        return [
            'fit' => true,
            'wrap' => 'nowrap',
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
}
