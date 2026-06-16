<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Keystone\Toolkit\Models\AppModel;
use Keystone\Toolkit\ToolkitServiceProvider;
use Tests\TestCase;

class ToolkitPackageTest extends TestCase
{
    public function test_toolkit_is_discovered_and_registers_its_views(): void
    {
        $this->assertTrue(class_exists(ToolkitServiceProvider::class));
        $this->assertTrue(view()->exists('toolkit::form.field'));
        $this->assertTrue(view()->exists('toolkit::components.layouts.app'));
        $this->assertSame('', trim(Blade::render('<x-toolkit::notification />')));
    }

    public function test_model_properties_drive_fillable_casts_forms_tables_and_rules(): void
    {
        $model = new ToolkitExampleModel();
        $schema = ToolkitExampleModel::getSchema();

        $this->assertSame(
            ['name', 'description', 'published', 'metadata'],
            $model->getFillable()
        );
        $this->assertSame('array', $model->getCasts()['metadata']);
        $this->assertArrayHasKey('secret', $schema['form']);
        $this->assertArrayNotHasKey('secret', $schema['table']);
        $this->assertSame([
            'name' => ['alpha', 'required'],
            'description' => ['string', 'required'],
            'published' => ['nullable', 'boolean'],
            'metadata' => ['json'],
        ], ToolkitExampleModel::rules());
    }
}

class ToolkitExampleModel extends AppModel
{
    protected $properties = [
        'name',
        'description',
        'published' => [
            'type' => 'boolean',
            'label' => 'Published',
            'rules' => ['nullable', 'boolean'],
        ],
        'metadata' => [
            'type' => 'json',
        ],
        'secret' => [
            'type' => 'hidden',
            'fillable' => false,
            'rules' => [],
        ],
    ];
}
