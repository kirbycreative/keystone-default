<?php

namespace Keystone\Toolkit\Properties;

class Boolean extends Property
{
    public $type = 'boolean';

    public $fillable = true;

    public $label = 'Boolean';

    public $rules = [
        'type' => 'boolean',
        'required',
    ];

    public $options = [
        'No' => 0,
        'Yes' => 1,
    ];

    public function table()
    {
        return [];
    }

    public function form()
    {
        return [
            'type' => 'select',
            'options' => $this->options,
        ];
    }
}
