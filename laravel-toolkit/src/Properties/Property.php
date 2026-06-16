<?php

namespace Keystone\Toolkit\Properties;

class Property
{


    protected $type = 'string';

    protected $fillable = true;

    protected $label = 'Property';

    protected $prepend = '';

    protected $append = '';

    protected $rules = [];

    function __construct($propertyName = null, $label = null)
    {
        if (!empty($label)) {
            $this->label = $label;
        } elseif ($propertyName) {
            $this->label = ucwords(str_replace('_', ' ', $propertyName));
        }
    }

    public function configuration(): array
    {
        $configuration = [
            'type' => $this->type,
            'fillable' => $this->fillable,
            'label' => $this->label,
            'prepend' => $this->prepend,
            'append' => $this->append,
            'rules' => $this->rules,
        ];

        if (property_exists($this, 'cast')) {
            $configuration['cast'] = $this->cast;
        }

        return $configuration;
    }

    public function table()
    {
        return null;
    }

    public function form()
    {
        return null;
    }
    /*
    public function accessor($value)
    {
        return $value;
    }

    public function mutator($value)
    {
        return $value;
    }
        */
}
