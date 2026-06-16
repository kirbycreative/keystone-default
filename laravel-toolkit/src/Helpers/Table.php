<?php

namespace Keystone\Toolkit\Helpers;

use Illuminate\Support\Arr;
use Keystone\Toolkit\Helpers\VirtualDom\Element;
use Illuminate\Database\Eloquent\Collection;

class Table
{


    static function fromModel($model, $options = [])
    {
        $paginator = $model::all()->paginate(15);
        $options['paginator'] = $paginator;
        $inspected = inspect($model);
        return new Table($inspected['value'], $options);
    }

    public $data = [];
    public $header = null;
    public $rows = null;
    public $headings = [];
    public $options = [];

    function __construct($data = [], $options = [])
    {

        $this->data = $data;
        $this->options = $options;

        $this->initialize();
    }

    private function getColumnAttributes($name, $config = [])
    {
        $attrs = [];
        $classes = $config['class'] ?? [];
        $cid = 'col--' . str_replace('_', '-', $name);
        $styles = [];
        $selector = '.' . $cid;
        $attrs['class'] = $cid;

        if (isset($config['align'])) {
            $styles['text-align'] = $config['align'];
        }

        if (isset($config['wrap'])) {
            $styles['white-space'] = 'nowrap';
        }

        if (isset($config['width'])) {
            if (isset($config['width']['min'])) {
                $styles['min-width'] = is_integer($config['width']['min']) ? $config['width']['min'] . 'px' : $config['width']['min'];
            }
        }

        if (isset($config['fit'])) {
            $styles['width'] = '1%';
            $styles['white-space'] = 'nowrap';
        }

        page()->style([
            $selector => $styles
        ]);

        return $attrs;
    }

    private function columnValue($value, $row, $config)
    {

        if (isset($config['format'])) {
            $value = $config['format']($value, $row);
        } elseif (isset($config['accessor'])) {
            $value = $config['accessor']($value, $row);
        }

        if (isset($config['truncate'])) {
            if (strlen($value) > $config['truncate']) {
                $value = substr($value, 0, $config['truncate']);
                $value .= "...";
            }
        }

        if (isset($config['link'])) {
            $value = new Element('a', [
                'href' => $config['link']['href']
            ], [$config['link']['text']]);
        }


        if (is_array($value)) {
            $value = implode(", ", $value);
        }

        return $value;
    }

    public function render()
    /**
     * Renders the table as an HTML string
     *
     * @return string
     */
    {
        //dd($this);
        // dd($this->rows);
        $html = [];
        if ($this->options['add_link']) {
            $addLink = new Element('a', [
                'class' => 'button-sm border-slate-50 float-right',
                'href' => $this->options['add_link']
            ], ['Create New']);
            $html[] = $addLink->render();
        }
        $html[] = '<div class="table-wrapper">';
        $html[] = '<table class="table w-full relative">';
        if (!empty($this->header)) $html[] = $this->header->render();
        if (!empty($this->rows)) $html[] = $this->rows->render('content');

        $html[] = '</table>';
        $html[] = '</div>';

        if ($this->options['add_link']) {
            $html[] = $addLink->render();
        }

        //$html[] = 

        return implode("\n", $html);
    }

    function initialize()
    {

        $inspected = inspect($this->data);

        if ($this->data instanceof Collection && $this->data->count() == 0) {
            return;
        }

        if ($this->data instanceof Collection && $this->data->count() > 0) {
            $model = $this->data->getQueueableClass();
            new $model();
            // $modelClass = get_class($model);
            $schema = $model::$schemas[$model];
            $properties = $schema['properties'];
            $tableConfig = $schema['table'];
            $labels = method_exists($model, 'labels') ? $model::labels() : null;
            $records = $this->data->all();
        }




        // if (!Arr::isAssoc($this->data)) {
        // $record = $this->data[0];
        // }
        $columns = [];
        $header = new Element('tr', ['class' => 'border-b-1 border-slate-50']);
        foreach ($tableConfig as $key => $value) {

            if (is_string($value)) {
                $value = [
                    'label' => ucfirst($value)
                ];
            }

            $attributes = $this->getColumnAttributes($key, $value);
            $label = '';
            if (isset($value['th']) && isset($value['th']['label'])) {
                $label = $value['th']['label'];
            } elseif (isset($value['label'])) {
                $label = $value['label'];
            } else if (isset($properties[$key]['label'])) {
                $label = $properties[$key]['label'];
            }

            if (isset($value['th']) && isset($value['th']['attributes'])) {
                $value['th']['attributes']['class'] = $attributes['class'];
                $attributes = $value['th']['attributes'];
            }

            $heading = Arr::get($this->options, $key, $key) ?? $key;
            $columns[$key] = [
                'label' => $label,
                'attributes' => $attributes,
                'config' => $value
            ];
            $header->addChild('th', $attributes, $label);
        }

        if (isset($this->options['actions'])) {
            $header->addChild('th', ['class' => 'col--actions', 'colspan' => count($this->options['actions'])], 'Actions');
        }

        $rows = new Element('tbody', ['class' => "table-body"]);
        foreach ($records as $record) {
            $data = $record->toArray();

            $row = $rows->addChild('tr', ['id' => "row-{$record->id}", 'class' => 'row']);
            foreach ($columns as $key => $options) {
                $label = $options['label'];
                $className = $options['attributes']['class'];
                $config = $options['config'];
                $value = $this->columnValue($data[$key], $data, $config);



                $row->addChild('td', ['class' => $className], $value);
            }

            if (isset($this->options['actions'])) {
                if (method_exists($record, 'actions')) {
                    $actions = $record->actions();
                    foreach ($this->options['actions'] as $action) {
                        if (isset($actions[$action])) {
                            $link = '<a href="' . $actions[$action] . '" class="action-link">' . ucfirst($action) . '</a>';
                            $row->addChild('td', ['class' => 'col--actions'], [$link]);
                        }
                    }
                }
            }
        }


        $this->rows = $rows;

        $this->header = $header;
    }
}
