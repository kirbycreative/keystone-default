# Models and Properties

The toolkit keeps field metadata in one place: the model's `$properties`
definition. That schema drives mass-assignment fields, Eloquent casts,
validation rules, form fields, and table columns.

## What changed

Toolkit classes no longer use the consuming application's `App\...` namespace.
Update model imports from:

```php
use App\Models\AppModel;
```

to:

```php
use Keystone\Toolkit\Models\AppModel;
```

Reusable property classes now live under `Keystone\Toolkit\Properties`.
Application-specific models, including `App\Models\User`, remain in the Laravel
application and are not supplied by the package.

## Define a model

Extend the toolkit model and define a protected `$properties` array:

```php
<?php

namespace App\Models;

use Keystone\Toolkit\Models\AppModel;

class Project extends AppModel
{
    protected $properties = [
        'name',
        'description',
        'published' => [
            'type' => 'boolean',
            'label' => 'Published',
            'rules' => ['required', 'boolean'],
        ],
        'metadata' => [
            'type' => 'json',
            'rules' => ['nullable', 'array'],
        ],
    ];
}
```

Simple string entries infer the property type from the field name. In the
example, `name` uses the built-in `Name` property and `description` uses
`Description`.

Use an associative entry when the database column name and property type differ
or when defaults need to be overridden.

## Property options

Each associative property definition supports:

| Key | Purpose |
| --- | --- |
| `type` | Built-in property type to load. |
| `label` | Human-readable form and table label. |
| `fillable` | Whether the field is added to Eloquent's fillable list. |
| `cast` | Eloquent cast, such as `array`, `boolean`, or `datetime:Y-m-d`. |
| `rules` | Laravel validation rules for this field. |
| `form` | Form field configuration, or `false` to exclude it from forms. |
| `table` | Table column configuration, or `false` to exclude it from tables. |
| `prepend` | Prefix metadata available in the generated schema. |
| `append` | Suffix metadata available in the generated schema. |

Example with all common overrides:

```php
protected $properties = [
    'is_featured' => [
        'type' => 'boolean',
        'label' => 'Featured',
        'fillable' => true,
        'cast' => 'boolean',
        'rules' => ['required', 'boolean'],
        'form' => [
            'type' => 'select',
            'options' => [
                'No' => 0,
                'Yes' => 1,
            ],
        ],
        'table' => [
            'label' => 'Featured',
            'align' => 'center',
            'fit' => true,
        ],
    ],
];
```

Model options override the built-in property's defaults.

## Built-in properties

| Type | Default behavior |
| --- | --- |
| `boolean` | Fillable; `boolean|required`; select field with Yes/No options. |
| `category` | Fillable; cast to `array`; checkbox field with category options. |
| `company` | Fillable; `string|required`. |
| `companyName` | Fillable; `alpha|required`; text field. |
| `date` | Fillable; cast to `datetime:Y-m-d`; date field. |
| `dateTime` | Fillable; cast to `datetime:Y-m-d H:i:s`; date field. |
| `description` | Fillable; `string|required`; wide, truncated table column. |
| `hidden` | Fillable; hidden form field; excluded from generated tables. |
| `image` | Fillable; `string|required`; file field and image table preview. |
| `json` | Fillable; cast to `array`; `json`; non-wrapping table column. |
| `longText` | Fillable; textarea field; paragraphs are normalized on assignment. |
| `name` | Fillable; `alpha|required`; text field. |
| `slug` | Fillable; `string|required`; text field and compact table column. |
| `tags` | Fillable; `array|nullable`. |
| `text` | Fillable; `string|required`; standard text field. |
| `url` | Fillable; `string|required`. Add Laravel's `url` rule when required. |
| `version` | Fillable; `float|required`; compact table column with `v` prefix metadata. |

Type names are converted to property class names. For example, `dateTime`
resolves to `Keystone\Toolkit\Properties\DateTime`.

## Validation rules

`AppModel::rules()` returns rules from the same property schema:

```php
public function store(Request $request)
{
    $data = $request->validate(Project::rules());

    return Project::create($data);
}
```

Rules declared as keyed metadata in a built-in property are normalized to a
standard Laravel rule list. For example:

```php
[
    'type' => 'string',
    'required',
]
```

becomes:

```php
['string', 'required']
```

Override rules for one model directly in its property definition:

```php
protected $properties = [
    'website' => [
        'type' => 'url',
        'rules' => ['nullable', 'url', 'max:2048'],
    ],
];
```

For validation involving multiple fields or database state, extend the
generated rules:

```php
public static function rules(): array
{
    return array_merge(parent::rules(), [
        'slug' => ['required', 'string', 'max:255', 'unique:projects,slug'],
    ]);
}
```

For update requests, keep record-specific rules in the request or controller so
the current record ID is explicit:

```php
$rules = Project::rules();
$rules['slug'] = [
    'required',
    'string',
    Rule::unique('projects', 'slug')->ignore($project),
];

$data = $request->validate($rules);
```

## Fillable fields and casts

Properties with `fillable => true` are automatically added to the model's
fillable list. A property cast is added to Eloquent's cast map.

```php
$project = new Project();

$project->getFillable();
// ['name', 'description', 'published', 'metadata']

$project->getCasts()['metadata'];
// 'array'
```

Set `fillable` to `false` for fields that must never be mass assigned:

```php
'internal_token' => [
    'type' => 'hidden',
    'fillable' => false,
    'rules' => [],
],
```

## Form configuration

The generated form schema is available through `getSchema()`:

```php
$schema = Project::getSchema();
$fields = $schema['form'];
```

Override a field:

```php
'status' => [
    'type' => 'text',
    'form' => [
        'type' => 'select',
        'label' => 'Project status',
        'options' => [
            'Draft' => 'draft',
            'Published' => 'published',
        ],
    ],
],
```

Exclude a field from generated forms:

```php
'calculated_total' => [
    'type' => 'text',
    'form' => false,
],
```

Render the toolkit form builder:

```php
use Keystone\Toolkit\Forms\Form;

$form = (new Form($project))
    ->setAction(route('projects.update', $project))
    ->setMethod('POST');
```

```blade
{!! $form->build() !!}
```

The form builder uses package-owned views under the `toolkit::form.*`
namespace. JavaScript remains application-owned; the package does not assume a
`forms.js` Vite entry.

## Table configuration

The generated table schema is:

```php
$columns = Project::getSchema()['table'];
```

Common table options include:

```php
'description' => [
    'type' => 'description',
    'table' => [
        'label' => 'Summary',
        'align' => 'left',
        'truncate' => 120,
        'width' => ['min' => 240],
    ],
],
```

Set `table` to `false` to exclude a field:

```php
'internal_token' => [
    'type' => 'hidden',
    'table' => false,
],
```

## Inspect the complete schema

```php
$schema = Project::getSchema();
```

The schema contains:

```php
[
    'fillable' => [],
    'casts' => [],
    'dates' => [],
    'table' => [],
    'form' => [],
    'properties' => [],
]
```

`properties` contains normalized metadata. The other keys are the behavior
derived from that metadata.

Models may inspect the completed schema with an optional hook:

```php
protected function onSchemaReady(array $schema): void
{
    // Inspect or configure model-owned behavior after schema generation.
}
```

Do not mutate the property definition per request. Schemas are cached by model
class for the lifetime of the PHP process.

## Keystone admin models

The client admin panel models should also extend `Keystone\Toolkit\Models\AppModel`.
Do not add separate `$fillable` arrays or `casts()` methods for new admin
models unless the toolkit cannot represent the behavior.

`App\Models\ContentAsset` uses `$properties` for:

| Property | Purpose |
| --- | --- |
| `title` | Optional display name for the uploaded source material. |
| `type` | Required asset category such as menu, promotion, advertisement, brand, photo, document, or other. |
| `notes` | Optional ingestion guidance, limited to 2000 characters. |
| `ingestion_result` | Array cast for future analysis output. |

`App\Models\PageSuggestion` uses `$properties` for:

| Property | Purpose |
| --- | --- |
| `title`, `slug`, `summary`, `rationale` | Suggested page structure and explanation. |
| `source_asset_ids` | Array cast of uploaded assets that informed the suggestion. |
| `suggested_copy` | Array cast for generated copy sections. |
| `status` | Review status: `suggested`, `approved`, or `rejected`. |
| `rejection_feedback` | Reviewer explanation required when a suggestion is denied. |
| `reviewed_at` | Datetime cast marking when approve or deny was submitted. |

Admin forms should be instances of `Keystone\Toolkit\Forms\Form`, including
login, logout, upload, review, approve, and deny actions. Custom row layouts can
still be rendered by passing a field-level `view`, but the enclosing `<form>`,
CSRF field, method spoofing, submit button, and normal field rendering should
come from the toolkit builder.

## Package updates

The Laravel application loads the package through the local Composer path
repository:

```bash
composer update keystone/laravel-toolkit
```

Composer links `laravel-toolkit` into `vendor` during development. The parent
application's Composer scripts publish CSS and fonts automatically after
installs and updates.

Production deployment uses the committed lockfile:

```bash
git submodule update --init --recursive
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
```

The Juice JavaScript repository is pinned as a Git submodule at
`resources/js/vendor/juice`. Do not clone or copy Juice separately during
deployment; the committed submodule pointer is the source of truth.
