<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Arr;
use Keystone\Toolkit\Helpers\VirtualDom\Element;
use Illuminate\Database\Eloquent\Relations\Relation;
use Keystone\Toolkit\Helpers\Table;
use Keystone\Toolkit\Helpers\Image;

if (!function_exists('inspect')) {

    function getProtectedProperty($object, $propertyName)
    {
        try {
            $reflection = new ReflectionClass($object);
            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);
            $value = $property->getValue($object);
        } catch (\Throwable $e) {
            $value = null;
        }
        return $value;
    }

    function getModelRelations($model)
    {
        $relations = [];
        $modelClass = get_class($model);
        $reflection = new ReflectionClass($modelClass);

        // Loop through all methods of the model
        foreach ($reflection->getMethods() as $method) {
            // Skip methods that are not defined by the user
            if ($method->class !== $modelClass || !$method->isPublic() || $method->getNumberOfParameters() > 0) {
                continue;
            }

            try {
                // Call the method to check if it returns a relation
                $result = $method->invoke($model);

                // Check if the result is a relationship
                if ($result instanceof Relation) {
                    $relations[$method->getName()] = [
                        'foreignKey' => $result->getForeignKeyName(),
                        'localKey' => $result->getLocalKeyName(),
                        'model' => $result->getRelated()
                    ];
                }
            } catch (\Throwable $e) {
                // Ignore any exceptions when calling the method
            }
        }

        return $relations;
    }

    function inspect($data)
    {
        $resp = [];
        $resp['type'] = gettype($data);
        $resp['value'] = $data;

        if (!is_array($data)) {
            $resp['className'] = get_class($data);
            $resp['classBaseName'] = class_basename($data);
        }

        if ($data instanceof Illuminate\Database\Eloquent\Collection) {
            $resp['type'] = 'collection';
            $resp['count'] = $data->count();
            if ($resp['count'] > 0) {
                $resp['keys'] = $data->modelKeys();
                $resp['records'] = $data->all();
                $resp['related'] = $data->getQueueableRelations();
                $resp['primaryClass'] = $data->getQueueableClass();
                $resp['model'] = inspect(new $resp['primaryClass']());
            }
        } elseif ($data instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $resp['type'] = 'paginator';
            $resp['count'] = $data->total();
            $paginator = $data;
            $data = $data->items();
            $resp['value'] = $data;
        } elseif ($data instanceof Illuminate\Database\Eloquent\Model) {
            $resp['type'] = 'model';
            $resp['identifier'] = strtolower(preg_replace('/\B([A-Z])/', '_$1', $resp['classBaseName']));
            $resp['table'] = $data->getTable();
            $resp['primaryKey'] = $data->getKeyName();
            $resp['primaryKeyType'] = $data->getKeyType();
            $resp['columns'] = $data->getConnection()->getSchemaBuilder()->getColumnListing($data->getTable());
            $resp['with'] = $data->with;
            $resp['withCount'] = $data->withCount;
            $resp['relations'] = getModelRelations($data);
            if ($data->withCount > 0) {
                //array_push($resp['columns'], $data->with);
            }
        } elseif (is_array($data)) {
            $resp['count'] = count($data);
            $resp['keys'] = array_keys($data);
        }

        return $resp;
    }
}

if (!function_exists('table')) {

    function table($data = [], $options = [])
    {
        $table = new Table($data, $options);
        return $table->render();
    }

    function tableFromModel($data = [], $options = [])
    {

        $table = new Table($data, $options);
        return $table->render();
    }
}

if (!function_exists('image')) {
    function image($source = null)
    {
        return new Image($source);
    }
}

if (!function_exists('page')) {

    class StyleSheet {}

    class Page
    {

        static $instance = null;

        public $stylesheets = [];
        public $styles = [];
        public $js = [];
        private $classes = [];
        private $_vite = [];
        public $heading = null;
        public $description = null;
        public $title = null;

        /** Arbitrary data exposed to the browser as window.app.data via the head. */
        private $_data = [];

        public $id;
        public $class;
        public $url;
        public $path;
        public $head = [];

        function __construct($config = [])
        {

            $this->url = request()->getRequestUri();
            $this->path = request()->getPathInfo();
            $this->id = str_replace('/', '-', trim($this->path, "/"));

            if (!empty($config)) $this->apply($config);
        }

        public function apply($config = [])
        {

            if (isset($config['id'])) {
                $this->id = $config['id'];
            }

            if (isset($config['class'])) {
                foreach ((array) $config['class'] as $class) {
                    $this->classes[] = $class;
                }
            }

            if (isset($config['title'])) {
                $this->title = $config['title'];
            }

            if (isset($config['heading'])) {
                $this->heading = $config['heading'];
            }

            if (isset($config['description'])) {
                $this->description = $config['description'];
            }

            if (isset($config['js'])) {
                $this->js = array_merge($this->js, (array) $config['js']);
            }

            if (isset($config['stylesheets'])) {
                $this->stylesheets = array_merge($this->stylesheets, (array) $config['stylesheets']);
            }

            if (isset($config['vite'])) {
                foreach ((array) $config['vite'] as $path) {
                    $this->_vite[] = $path;
                }
            }

            if (isset($config['data']) && is_array($config['data'])) {
                $this->_data = array_merge($this->_data, $config['data']);
            }

            if (isset($config['head'])) {
                $this->head[] = $config['head'];
            }

            return $this;
        }

        public function vite($path)
        {
            $this->_vite[] = $path;
        }

        public function head($appends)
        {
            $this->head[] = $appends;
        }

        public function addClass($class)
        {
            $this->classes[] = $class;
        }

        public function class()
        {
            // Space-separated class list from configured classes plus any passed at call time.
            return implode(' ', array_merge($this->classes, func_get_args()));
        }

        public function setTitle($title)
        {
            $this->title = $title;
            return $this;
        }

        public function setHeading($heading)
        {
            $this->heading = $heading;
            return $this;
        }

        public function setDescription($description)
        {
            $this->description = $description;
            return $this;
        }

        /**
         * Set a key on the browser data bag (window.app.data).
         */
        public function setData($key, $value)
        {
            $this->_data[$key] = $value;
            return $this;
        }

        /**
         * Read the data bag — a single key, or the whole bag when no key is given.
         */
        public function data($key = null)
        {
            if ($key === null) {
                return $this->_data;
            }

            return $this->_data[$key] ?? null;
        }

        public function has($key)
        {
            return isset($this->_data[$key]) && $this->_data[$key] !== null;
        }

        /**
         * The data bag as JSON for the head's window.app bootstrap.
         */
        public function dataJson()
        {
            return json_encode($this->_data);
        }


        public function script($path)
        {
            $this->js[] = $path;
        }

        public function stylesheet($path)
        {
            $this->stylesheets[] = $path;
        }

        public function style($styles)
        {
            foreach ($styles as $selector => $properties) {
                if (isset($this->styles[$selector])) {
                    $this->styles[$selector] = array_merge($this->styles[$selector], $properties);
                } else {
                    $this->styles[$selector] = $properties;
                }
            }
        }

        private function renderJs()
        {
            $js = '';
            foreach ($this->js as $script) {
                $js .= '<script src="' . $script . '"></script>';
            }
            return $js;
        }

        private function renderCss()
        {
            $css = '';
            foreach ($this->stylesheets as $stylesheet) {
                $css .= '<link rel="stylesheet" href="' . $stylesheet . '" />';
            }
            if ($this->styles) {
                $css .= '<style>';
                foreach ($this->styles as $selector => $style) {
                    $css .= $selector . " {\n";
                    if (is_string($style)) {
                        $css .= $style;
                    } elseif (is_array($style)) {
                        foreach ($style as $property => $value) {
                            $css .= "\t" . $property . ": " . $value . ";\n";
                        }
                    }
                    $css .= $selector . "}\n\n";
                }
                $css .= '</style>';
            }
            return $css;
        }

        public function render($asset = null)
        {
            switch ($asset) {
                case 'css':
                    return $this->renderCss();
                    break;
                case 'js':
                    return $this->renderJs();
                    break;
                case 'head':
                    if (!empty($this->head)) {
                        return \Keystone\Toolkit\Helpers\VirtualDom\Render::html($this->head);
                    } else {
                        return '';
                    }
                    break;
            }
        }

        public function get($type = null)
        {
            switch ($type) {
                case 'vite':
                    return $this->_vite;
                    break;
            }
        }
    }



    function page($config = null)
    {
        if (!empty(Page::$instance)) {
            if (!empty($config)) {
                Page::$instance->apply($config);
            }
            return Page::$instance;
        }
        $instance = new Page($config);
        Page::$instance = $instance;
        return $instance;
    }
}
