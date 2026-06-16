# Keystone Laravel Toolkit

Reusable Laravel models, property schemas, form rendering, Blade components,
and UI assets.

## Install from this repository

The parent Laravel application registers this directory as a Composer path
repository. Install or refresh it with:

```bash
composer update keystone/laravel-toolkit
```

Composer links the local package during development, and Laravel discovers
`ToolkitServiceProvider` automatically. The parent application's Composer
scripts publish the package assets after installs and updates.

## Usage

Extend the package model when an application model needs property schemas:

```php
use Keystone\Toolkit\Models\AppModel;

class Project extends AppModel
{
    protected $properties = [
        'name',
        'description',
    ];
}
```

Package Blade components use the `toolkit` prefix:

```blade
<x-toolkit::form>
    ...
</x-toolkit::form>
```

Views can be customized by publishing them:

```bash
php artisan vendor:publish --tag=keystone-toolkit-views
```

Application-specific routes, models, service bindings, and Vite entries remain
owned by the consuming Laravel application.

## Model documentation

See [docs/models-and-properties.md](docs/models-and-properties.md) for:

- migrating existing models to the package namespace
- defining fields with `$properties`
- built-in property types and their defaults
- generated fillable fields, casts, forms, tables, and validation rules
- overriding rules and field-level behavior
- using the generated schema and form builder
