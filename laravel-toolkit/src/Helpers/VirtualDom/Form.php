<?php

namespace Keystone\Toolkit\Helpers\VirtualDom;

use Keystone\Toolkit\Helpers\VirtualDom\Element;

$globalAttributes = ["accesskey", "class", "contenteditable", "data-*", "dir", "draggable", "hidden", "id", "lang", "spellcheck", "style", "tabindex", "title", "translate"];

$formElementTypes = ["input", "label", "select", "textarea", "button", "fieldset", "legend", "datalist", "output", "option", "optgroup"];

$formInputTypes = ["button", "checkbox", "color", "date", "datetime-local", "email", "file", "hidden", "image", "month", "number", "password", "radio", "range", "reset", "search", "submit", "tel", "text", "time", "url", "week"];

class Form
{

    static function extractOptions(&$attrs)
    {
        $extractable = ['default'];
        $options = [];
        foreach ($extractable as $prop) {
            if (isset($attrs[$prop])) {
                switch ($prop) {
                    case 'default':
                        if (empty($attrs['value'])) {
                            $attrs['value'] = $attrs['default'];
                        }
                        break;
                    default:
                        $options[$prop] = $attrs[$prop];
                        unset($attrs[$prop]);
                }
            }
        }

        return $options;
    }


    static function create($name, $options = [])
    {

        $action = $options['action'] ?? 'return';
        $method = $options['method'] ?? 'POST';

        $name = str_replace('_', '-', $name);

        $id = 'form--' . strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $name));

        $form = new Element('form', ['id' => $id, 'name' => $name, 'action' => $action, 'method' => $method]);

        return $form;
    }

    static function getId($name)
    {
        if (strpos($name, '[')) {
            $name = str_replace('][', '--', $name);
            $name = str_replace('[', '--', $name);
            $name = str_replace(']', '', $name);
        }
        return 'input--' . str_replace('_', '-', $name);
    }

    static function button($text, $onclick)
    {
        return new Element('button', ['onclick' => $onclick]);
    }

    static function submit($text)
    {
        return new Element('input', ['type' => "submit", 'value' => $text]);
    }

    static function hidden($name, $value, $attributes = [])
    {

        $attributes['id'] = $attributes['id'] ?? self::getId($name);
        $attributes['name'] = $attributes['name'] ?? $name;
        $attributes['type'] = 'hidden';
        $attributes['value'] = $value;

        return new Element('input', $attributes);
    }

    static function checkbox($name, $value, $checked, $attributes = [])
    {


        $attributes['id'] = $attributes['id'] ?? self::getId($name);
        $attributes['name'] = $attributes['name'] ?? $name;
        $attributes['type'] = 'checkbox';
        $attributes['value'] = $value;

        if ($checked) $attributes['checked'] = null;

        return new Element('input', $attributes);
    }

    static function radio($name, $value, $checked, $attributes = [])
    {

        $attributes['id'] = $attributes['id'] ?? self::getId($name);
        $attributes['name'] = $attributes['name'] ?? $name;
        $attributes['type'] = 'radio';
        $attributes['value'] = $value;

        if ($checked) $attributes['checked'] = null;

        return new Element('input', $attributes);
    }

    static function text($name, $value = '', $attributes = [])
    {

        $attributes['id'] = $attributes['id'] ?? self::getId($name);
        $attributes['name'] = $attributes['name'] ?? $name;
        $attributes['type'] = 'text';
        $attributes['value'] = $value;

        return new Element('input', $attributes);
    }

    static function input($type, $name, $value, $attributes = [])
    {

        $attributes['id'] = $attributes['id'] ?? self::getId($name);
        $attributes['name'] = $attributes['name'] ?? $name;
        $attributes['type'] = $type;
        $attributes['value'] = $value;

        return new Element('input', $attributes);
    }

    static function date($name, $value, $attributes = [])
    {

        $attributes['id'] = $attributes['id'] ?? self::getId($name);
        $attributes['name'] = $attributes['name'] ?? $name;
        $attributes['type'] = 'date';
        $attributes['value'] = $value;

        return new Element('input', $attributes);
    }

    static function datetime($name, $value, $attributes = [])
    {

        $attributes['id'] = $attributes['id'] ?? self::getId($name);
        $attributes['name'] = $attributes['name'] ?? $name;
        $attributes['type'] = 'datetime-local';
        $attributes['value'] = $value;

        return new Element('input', $attributes);
    }

    static function textarea($name, $value, $attributes = [])
    {

        $attributes['id'] = $attributes['id'] ?? self::getId($name);
        $attributes['name'] = $attributes['name'] ?? $name;
        $attributes['value'] = $value;

        return new Element('textarea', $attributes, [$value]);
    }

    static function select($name, $value = '', $options = [], $attributes = [])
    {

        $attributes['id'] = $attributes['id'] ?? self::getId($name);
        $attributes['name'] = $attributes['name'] ?? $name;
        $attributes['value'] = $value;

        return new Element('select', $attributes, self::options($options, $value));
    }

    static function optgroup() {}

    static function description($text)
    {
        return new Element('div', ['class' => 'field-description'], [$text]);
    }

    static function field($type, ...$args)
    {

        $fieldSpecialAttrs = ['label', 'label_placement', 'description', 'field_class'];

        $attributes = is_array(end($args)) ? end($args) : [];
        $name = $args[0];
        $value = $args[1];

        $special = [];

        foreach ($fieldSpecialAttrs as $fieldSAttr) {
            if (isset($attributes[$fieldSAttr])) {
                $special[$fieldSAttr] = $attributes[$fieldSAttr];
                unset($attributes[$fieldSAttr]);
            }
        }

        if (in_array($type, ['checkbox', 'radio'])) $special['label_placement'] = 'around';

        $label_placement = $special['label_placement'] ?? 'before';

        $classes = ['field', "type-{$type}"];

        if (isset($special['field_class'])) array_push($classes, $special['field_class']);

        if (isset($attributes['required'])) $classes[] = 'required';

        $field = new Element('div', ['class' => join(' ', $classes)]);

        $input = self::{$type}(...$args);

        $label = self::label($name, $special['label'] ?? null);

        $input_wrapper = new Element('div', ['class' => 'input-wrapper']);

        $field->children[] = $input_wrapper;

        if ($label_placement == 'after') {
            $input_wrapper->children[] = $input;
            array_push($field->children, $label);
        } elseif ($label_placement == 'around') {
            $field->children[] = $label;
            array_unshift($label->children, $input);
        } else {
            $input_wrapper->children[] = $input;
            array_unshift($field->children, $label);
        }

        if (isset($special['description']))
            $field->children[] = self::description($special['description']);

        return $field;
    }

    static function datalist($element_id, $options)
    {
        $children = [];
        foreach ($options as $option) {
            $children[] = self::option($option);
        }
        return new Element('datalist', ['id' => $element_id . '-list'], $children);
    }

    static function option($value, $text, $selected = false)
    {
        $children = [];
        $attrs = ['value' => $value];
        if (!empty($text)) $children = [$text];
        if ($selected) $attrs['selected'] = null;
        return new Element('option', $attrs, $children);
    }

    static function options($options, $default = '')
    {
        $elements = [];
        foreach ($options as $value => $text) {
            if (is_array($text)) {
                $elements[] = self::option($text['value'], $text['label'], $text['value'] == $default);
            } else {
                $k = is_numeric($value) ? $text : $value;
                $elements[] = self::option($k, $text, $k == $default);
            }
        }
        return $elements;
    }


    static function fieldset()
    {
        return new Element('fieldset');
    }

    static function ledgend($text)
    {
        return new Element('ledgend', [], $text);
    }

    static function label($inputName, $text)
    {
        $inputId = self::getId($inputName);
        if (empty($text)) {
            $name = $inputName;
            if (strpos($inputName, '[')) {
                $last = strripos($inputName, '[');
                $name = substr($inputName, $last + 1);
                $name = str_replace(']', '', $name);
            }
            $text = ucwords(str_replace('_', ' ', $name));
        }
        return new Element('label', ['for' => $inputId], [$text]);
    }

    static function row($children)
    {
        return new Element('div', ['class' => 'row'], $children);
    }

    static function block($children)
    {
        return new Element('div', ['class' => 'block'], $children);
    }
}
