<?php

namespace App\Forms;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Vite;
use App\Models\AppModel;

class Form
{

    protected $method = 'POST';
    protected $actionRoute = '';
    protected $action = '';
    protected $data = [];
    protected $model = null;
    protected $schema = null;

    public function __construct($data = [])
    {
        if (!empty($this->actionRoute)) {
            $this->setActionRoute($this->actionRoute);
        }

        $this->data = $data;
    }

    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    public function setActionRoute($actionRoute)
    {
        $this->actionRoute = $actionRoute;
        $this->action = route($actionRoute);
        return $this;
    }

    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }


    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function setModel($model)
    {
        $this->model = $model;
        return $this;
    }

    public function setSchema($schema)
    {
        $this->schema = $schema;
        return $this;
    }

    public function validate() {}

    public function fields()
    {
        if (empty($this->schema)) {
            if (!empty($this->model)) {
                if ($this->model::hasSchema()) {
                    $this->schema = $this->model::getSchema();
                }
            } elseif ($this->data instanceof AppModel) {
                //  dd($this->data);
                $this->model = get_class($this->data);
                if ($this->model::hasSchema()) {
                    $this->schema = $this->model::getSchema();
                }
            } else {
                $formSchema = Arr::map($this->data, function (string $value, string $key) {
                    return ['type' => 'text', 'label' => $key];
                });
                $this->schema['form'] = $formSchema;
            }
        }

        // dd($this->schema);

        foreach ($this->schema as $key => $value) {
            $type = Arr::get($value, 'type', 'text');
            $name = $key;
            $label = Arr::get($value, 'label', $this->schema['properties'][$key]['label'] ?? $key);
            $attributes = Arr::get($value, 'attributes', []);
            $value = $this->data[$key] ?? null;
        }



        return $this->schema['form'];
    }

    public function submit()
    {
        return ['value' => 'Submit'];
    }

    public function build()
    {

        $fields = $this->fields();

        //dd($fields);

        $rendered = [];
        $types = Arr::pluck($fields, 'type');

        $hasFile = in_array('file', $types);
        $open = '<script type="module" src="' . Vite::asset('resources/js/forms.js') . '" ></script>';
        $open .= '<form ' . ($hasFile ? ' enctype="multipart/form-data" ' : '') . ' method="' . $this->method . '" action="' . $this->action . '">';
        array_push($rendered, $open);

        array_push($rendered, csrf_field());
        //dd($this->data);
        foreach ($fields as $key => $field) {

            $options = [
                'name' => $key,
                'value' => $this->data[$key] ?? null,
                'class' => 'form-control',
            ];

            $view = view($field['view'] ?? 'components.form.field', array_merge($options, $field))->render();

            array_push($rendered, $view);
        }
        $submit = view('components.form.submit', $this->submit())->render();
        array_push($rendered, $submit);
        array_push($rendered, '</form>');
        return Arr::join($rendered, "\n");
    }
}
