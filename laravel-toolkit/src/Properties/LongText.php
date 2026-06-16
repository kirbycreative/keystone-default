<?php

namespace Keystone\Toolkit\Properties;

use Keystone\Toolkit\Properties\Property;
use Illuminate\Database\Eloquent\Casts\Attribute;

class LongText extends Property
{
    public $type = 'longtext';

    public $fillable = true;

    public $label = 'Long Text';

    public $rules = [];

    public function table()
    {
        return [
            'width' => [
                'min' => 250,
            ],
            'truncate' => 150,
            'align' => 'left',
            'accessor' => function ($value) {
                return strip_tags($value);
            }
        ];
    }


    public function form()
    {
        return [
            'type' => 'textarea',

        ];
    }



    public function mutator($value)
    {
        return collect(explode("\n", $value))
            ->map(fn($paragraph) => trim(preg_replace('/[\r\n]+/', ' ', $paragraph)))
            ->filter()
            ->map(fn($paragraph) => "<p>$paragraph</p>")
            ->implode('');
        return $value;
    }
}
