<?php

namespace Keystone\Toolkit\Traits;

use Illuminate\Support\Str;

trait HasProperties
{

    public static $rules = [];
    public static $schemas = [];

    protected $fillable = [];
    protected $methodCache = [];
    protected $schema = [];
    public $methods = [];


    /**
     * Setup the schema for the model.
     *
     * @return void
     */
    private function setupSchema(): void
    {
        // If the schema is already set, then return
        if (isset(static::$schemas[$this->identifier])) {
            $this->schema = static::$schemas[$this->identifier];
            return;
        }

        // Initialize the schema
        $this->schema = [
            'fillable' => [],
            'casts' => [],
            'dates' => [],
            'table' => [],
            'form' => [],
            'properties' => [],
        ];

        // Iterate over the properties from main model file and initialize them
        foreach ($this->properties as $property => $options) {
            $formSchema = null;
            $tableSchema = null;

            // If the property is numeric, then split it into property and type
            if (is_numeric($property)) {
                [$property, $propertyType, $label] = array_pad(
                    preg_split('/\s+/', trim($options), 3),
                    3,
                    null
                );
                $propertyOptions = ['type' => $propertyType, 'label' => $label];
            } else {
                $propertyOptions = (array) $options;
            }

            $config = $this->getPropertySchema($property, $propertyOptions);

            if (array_key_exists('form', $config)) {
                $formSchema = $config['form'];
                unset($config['form']);
            }

            if (array_key_exists('table', $config)) {
                $tableSchema = $config['table'];
                unset($config['table']);
            }

            if (isset($config['fillable']) && $config['fillable'] == true && !in_array($property, $this->fillable)) {
                $this->schema['fillable'][] = $property;
            }

            if (isset($config['cast']) && !isset($this->casts[$property])) {
                $this->schema['casts'][$property] = $config['cast'];
                unset($config['cast']);
            }

            $this->schema['properties'][$property] = $config;

            $this->applySchemaProperty($property);

            if ($tableSchema !== false) {
                $this->schema['table'][$property] = $tableSchema ?? [];
            }

            if ($formSchema !== false) {
                $this->schema['form'][$property] = $formSchema ?? [
                    'label' => ucwords(str_replace('_', ' ', $property)),
                    'type' => 'text',
                ];
            }
        }

        // Set the schema
        static::$schemas[$this->identifier] = $this->schema;
    }

    private function applySchemaProperty($name)
    {
        //Apply each property to the model
        $propertySchema = $this->schema['properties'][$name];



        if (isset($propertySchema['cast'])) {
            $this->casts[$name] = $propertySchema['cast'];
        }

        if (isset($propertySchema['type']) && $propertySchema['type'] === 'date') {
            $this->dates[] = $name;
        }

        if (isset($propertySchema['accessor'])) {
            $accessorName = 'get' . Str::studly($name) . 'Attribute';
            $this->methods[$accessorName] = $propertySchema['accessor'];
        }

        if (isset($propertySchema['mutator'])) {
            $mutatorName = 'set' . Str::studly($name) . 'Attribute';
            $this->methods[$mutatorName] = $propertySchema['mutator'];
        }
    }

    private function ingestProperty($property, $propertyClass)
    {
        $instance = new $propertyClass($property);
        $config = $instance->configuration();

        if (method_exists($instance, 'accessor')) {
            $config['accessor'] = [$instance, 'accessor'];
        }

        if (method_exists($instance, 'mutator')) {
            $config['mutator'] = [$instance, 'mutator'];
        }

        foreach (['table', 'form'] as $cmethod) {
            $method = $instance->{$cmethod}();

            if ($method === false) {
                $config[$cmethod] = false;
                continue;
            }

            $method = (array) $method;
            $method['type'] ??= 'text';
            $method['label'] ??= $config['label'];
            $config[$cmethod] = $method;
        }

        return $config;
    }

    public function getPropertySchema($property, $modelDefined = [])
    {
        if (!isset($modelDefined['type']) || empty($modelDefined['type'])) {
            $type = $property;
        } else {
            $type = $modelDefined['type'];
        }

        $propertyClass = "\\Keystone\\Toolkit\\Properties\\" . ucwords(Str::camel($type));

        if (class_exists($propertyClass)) {
            return array_merge($this->ingestProperty($property, $propertyClass), $modelDefined);
        } else {
            return $modelDefined;
        }
    }
}
