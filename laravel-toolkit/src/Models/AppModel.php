<?php

namespace Keystone\Toolkit\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Keystone\Toolkit\Traits\HasProperties;
use Illuminate\Support\Str;

class AppModel extends Model
{

    use HasProperties, HasFactory;

    protected $fillable = [];
    protected $dates = [];
    protected $properties = [];

    protected $identifier;

    public function __construct(array $attributes = [])
    {
        $this->identifier = get_class($this);
        // dd($this->identifier);
        $this->beforeInit();
        parent::__construct($attributes);
        $this->afterInit();

        // dd(static::$schema);
    }

    private function beforeInit()
    {
        $this->setupSchema();

        foreach ($this->schema['fillable'] as $fillable) {
            if (!in_array($fillable, $this->fillable)) {
                $this->fillable[] = $fillable;
            }
        }

        foreach ($this->schema['casts'] as $property => $cast) {
            if (!isset($this->casts[$property])) {
                $this->casts[$property] = $cast;
            }
        }

        if (method_exists($this, 'onSchemaReady')) {
            $this->onSchemaReady($this->schema);
        }
    }

    private function afterInit()
    {
        if (method_exists($this, '_afterInit')) {
            $this->_afterInit();
        }
    }

    private function getPropertyOptions($key): array
    {
        return $this->properties[$key] ?? [];
    }

    public static function rules(): array
    {
        $rules = [];

        foreach (static::getSchema()['properties'] as $property => $config) {
            if (!isset($config['rules']) || $config['rules'] === []) {
                continue;
            }

            $rules[$property] = is_array($config['rules'])
                ? array_values($config['rules'])
                : $config['rules'];
        }

        return $rules;
    }

    public function addFillable($fillable)
    {
        $this->fillable[] = $fillable;
    }

    protected static function getMutatorMethods($class)
    {
        preg_match_all('/(?<=^|;)get([^;]+?)Attribute(;|$)/', implode(';', get_class_methods($class)), $matches);

        return $matches[1];
    }

    public function hasGetMutator($key)
    {
        $accessorName = 'get' . Str::studly($key) . 'Attribute';
        return method_exists($this, $accessorName) || isset($this->methods[$accessorName]);
    }

    protected function mutateAttribute($key, $value)
    {
        $accessorName = 'get' . Str::studly($key) . 'Attribute';
        return isset($this->methods[$accessorName]) ? $this->methods[$accessorName]($value) : $this->{$accessorName}($value);
    }

    protected function setMutatedAttributeValue($key, $value)
    {
        $mutatorName = 'set' . Str::studly($key) . 'Attribute';
        return  isset($this->methods[$mutatorName]) ? $this->methods[$mutatorName]($value) : $this->{$mutatorName}($value);
    }

    public function hasSetMutator($key)
    {
        $mutatorName = 'set' . Str::studly($key) . 'Attribute';
        return method_exists($this, $mutatorName) || isset($this->methods[$mutatorName]);
    }

    public static function hasSchema()
    {
        $className = get_called_class();
        return isset(static::$schemas[$className]);
    }

    public static function getSchema()
    {
        $className = get_called_class();
        if (isset(static::$schemas[$className])) {
            return static::$schemas[$className];
        } else {
            $inst = new $className();
            return $inst->schema;
        }
    }
}
