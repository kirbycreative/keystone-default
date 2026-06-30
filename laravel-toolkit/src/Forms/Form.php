<?php

namespace Keystone\Toolkit\Forms;

use Illuminate\Support\Arr;
use Keystone\Toolkit\Models\AppModel;

class Form
{

    protected $method = 'POST';
    protected $actionRoute = '';
    protected $action = '';
    protected $data = [];
    protected $model = null;
    protected $schema = null;
    protected $attributes = [];
    protected $submit = ['value' => 'Submit'];

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
        $this->method = strtoupper($method);
        return $this;
    }

    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function setSubmit(string $value, array $attributes = [])
    {
        $this->submit = [
            'value' => $value,
            'attributes' => $attributes,
        ];

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

        return $this->schema['form'];
    }

    public function submit()
    {
        return $this->submit;
    }

    public function build()
    {

        $fields = $this->fields();

        //dd($fields);

        $rendered = [];
        $types = Arr::pluck($fields, 'type');

        $hasFile = in_array('file', $types);
        $formMethod = in_array($this->method, ['GET', 'POST']) ? $this->method : 'POST';
        $attributes = array_merge($this->attributes, [
            'method' => $formMethod,
            'action' => $this->action,
        ]);

        if ($hasFile) {
            $attributes['enctype'] = 'multipart/form-data';
        }

        $open = '<form ' . $this->renderAttributes($attributes) . '>';
        array_push($rendered, $open);

        array_push($rendered, csrf_field());

        if ($formMethod !== $this->method) {
            array_push($rendered, method_field($this->method));
        }

        $formId = $attributes['id'] ?? null;
        $formInfoFor = $formId ? ' for="' . e($formId) . '"' : '';
        array_push($rendered, '<form-info' . $formInfoFor . '></form-info>');

        $propertyRules = $this->modelRulesByField();

        foreach ($fields as $key => $field) {
            $name = $field['name'] ?? $key;
            $hasExplicitValue = array_key_exists('value', $field);

            // Required fields get the `required` attribute. Any other model rules (max, in, etc.)
            // ride along as a `validation` string so model-defined rules are always enforced —
            // juice merges/dedupes these with field-name presets, so the model wins when set.
            if (isset($propertyRules[$key])) {
                if (! isset($field['required'])) {
                    $field['required'] = in_array('required', $propertyRules[$key], true);
                }
                if (empty($field['validation'])) {
                    $field['validation'] = $this->validationFromRules($propertyRules[$key]);
                }
            }

            $options = [
                'name' => $name,
                'value' => $hasExplicitValue
                    ? $field['value']
                    : old($this->oldInputName($name), $this->data[$key] ?? null),
                'class' => 'form-control',
            ];

            $view = view($field['view'] ?? 'toolkit::form.field', array_merge($options, $field))->render();

            array_push($rendered, $view);
        }
        $submit = view('toolkit::form.submit', $this->submit())->render();
        array_push($rendered, $submit);
        array_push($rendered, '</form>');
        return Arr::join($rendered, "\n");
    }

    protected function renderAttributes(array $attributes): string
    {
        return collect($attributes)
            ->filter(fn ($value) => $value !== null && $value !== false)
            ->map(fn ($value, $key) => $value === true ? e($key) : e($key) . '="' . e($value) . '"')
            ->implode(' ');
    }

    protected function oldInputName(string $name): string
    {
        return str_replace('[]', '', $name);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function modelRulesByField(): array
    {
        if (empty($this->model) || ! $this->model::hasSchema()) {
            return [];
        }

        $rules = [];
        foreach ($this->model::getSchema()['properties'] ?? [] as $property => $config) {
            if (! empty($config['rules'])) {
                $rules[$property] = is_array($config['rules'])
                    ? $config['rules']
                    : [$config['rules']];
            }
        }

        return $rules;
    }

    /**
     * Map a model's Laravel rules to juice's validation string, dropping `required` (handled by the
     * required attribute) and type-only noise that juice infers natively.
     *
     * @param  array<int, mixed>  $rules
     */
    protected function validationFromRules(array $rules): ?string
    {
        $skip = ['required', 'nullable', 'string', 'boolean', 'integer', 'array', 'date', 'datetime'];
        $juice = [];

        foreach ($rules as $rule) {
            if (! is_string($rule)) {
                continue;
            }

            if (in_array(explode(':', $rule, 2)[0], $skip, true)) {
                continue;
            }

            $juice[] = $rule;
        }

        return $juice === [] ? null : implode('|', $juice);
    }
}
