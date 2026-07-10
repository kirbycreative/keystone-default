<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Keystone\Toolkit\Models\AppModel;

class Onboarding extends AppModel
{
    public const SCOPE_REGIONAL = 'regional';
    public const SCOPE_NATIONAL = 'national';

    public const STEP_DNS = 1;
    public const STEP_BRAND = 2;
    public const STEP_INSPIRATION = 3;
    public const STEP_LAUNCH = 4;

    /**
     * Properties are the single source of truth: they drive fillable, casts, validation rules
     * (Onboarding::rules()) and the Step 2 brand form, which is generated straight from this array.
     * Fields marked form=false are state/handoff columns not shown in the brand form.
     */
    protected $properties = [
        'user_id' => ['fillable' => true, 'type' => 'integer', 'form' => false],
        'step' => ['fillable' => true, 'type' => 'integer', 'form' => false],
        'dns_verified' => ['fillable' => true, 'cast' => 'boolean', 'form' => false],

        'company_name' => [
            'fillable' => true,
            'rules' => ['required', 'string', 'max:255'],
            'form' => ['type' => 'text', 'label' => 'Company name', 'placeholder' => 'Your business name'],
        ],
        'business_category' => [
            'fillable' => true,
            'rules' => ['required', 'string', 'max:255'],
            'form' => ['type' => 'text', 'label' => 'Business category', 'placeholder' => 'e.g. Italian restaurant, law firm, gym'],
        ],
        'slogans' => [
            'fillable' => true,
            'rules' => ['nullable', 'string', 'max:2000'],
            'form' => ['type' => 'textarea', 'label' => 'Slogans or taglines', 'help' => 'Optional — one per line.'],
        ],
        'region' => [
            'fillable' => true,
            'rules' => ['required', 'string', 'max:255'],
            'form' => ['type' => 'text', 'label' => 'Primary location', 'placeholder' => 'e.g. Austin, TX or United States'],
        ],
        'region_scope' => [
            'fillable' => true,
            'rules' => ['required', 'in:regional,national'],
            'form' => [
                'class' => "flex:static w:50",
                'type' => 'select',
                'label' => 'Audience reach',
                'options' => ['Regional / local' => self::SCOPE_REGIONAL, 'Countrywide' => self::SCOPE_NATIONAL],
            ],
        ],
        'logo' => [
            'fillable' => false,
            'form' => ['type' => 'image', 'label' => 'Logo', 'view' => 'admin.onboarding.partials.logo-field'],
        ],
        'primary_colors' => [
            'fillable' => true,
            'cast' => 'array',
            'form' => ['type' => 'text', 'label' => 'Primary colors', 'view' => 'admin.onboarding.partials.colors-field'],
        ],

        'logo_disk' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'logo_path' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'inspiration_domains' => ['fillable' => true, 'cast' => 'array', 'form' => false],
        'suggested_sites' => ['fillable' => true, 'cast' => 'array', 'form' => false],
        'imports_started_at' => ['fillable' => true, 'type' => 'date', 'cast' => 'datetime', 'form' => false],
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk($this->logo_disk ?: config('filesystems.default'))->url($this->logo_path);
    }
}
