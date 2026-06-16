<?php

namespace Keystone\Toolkit\Properties;

use Keystone\Toolkit\Properties\Property;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Category extends Property
{
    static function options()
    {
        return [
            'Logo Design' => 'logo',
            'Web Design' => 'web-design',
            'Mobile App' => 'mobile',
            'Print' => 'print',
            'Motion & Animation' => 'animation',
            'Consulting' => 'consult',
            'Illustration' => 'illustration',
            '3D Modelling' => '3d-modelling',
            'Marketing' => 'marketing',
            'AI/ML' => 'ai-ml',
            'Programming' => 'programming',
            'Game Design' => 'game-design',
            'E-commerce' => 'ecommerce',
            'DevOps' => 'devops',
            'Data Science' => 'data-science',
        ];
    }

    static function allowed()
    {
        return array_values(static::options());
    }

    protected $type = 'category';

    protected $fillable = true;

    protected $label = 'Category';

    public $cast = 'array';

    protected $rules = [
        'type' => 'json',
        'required',
    ];

    public function form()
    {
        return [
            'type' => 'checkbox',
            'options' => static::options(),
        ];
    }

    public function table()
    {
        return [
            'accessor' => function ($value) {
                return implode(', ', $value);
            },
            'label' => 'Categories'
        ];
    }
}
