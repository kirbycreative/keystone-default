<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Keystone\Toolkit\Models\AppModel;

class Onboarding extends AppModel
{
    public const CONTENT_UNLOCKED_STAGES = ['content_ready', 'site_build', 'completed'];

    public const SCOPE_REGIONAL = 'regional';

    public const SCOPE_NATIONAL = 'national';

    public const STEP_DNS = 1;

    public const STEP_BRAND = 2;

    public const STEP_INSPIRATION = 3;

    public const STEP_MATERIALS = 4;

    public const STEP_LAUNCH = 5;

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
            'form' => [
                'type' => 'text',
                'label' => 'Company name',
                'placeholder' => 'Your business name',
                'wrapper_class' => 'field brand-form__identity-field',
                'fieldset' => 'About the company',
            ],
        ],
        'business_category' => [
            'fillable' => true,
            'rules' => ['required', 'string', 'max:255'],
            'form' => [
                'type' => 'text',
                'label' => 'Business category',
                'placeholder' => 'e.g. Italian restaurant, law firm, gym',
                'wrapper_class' => 'field brand-form__identity-field',
                'fieldset' => 'About the company',
            ],
        ],
        'region' => [
            'fillable' => true,
            'rules' => ['required', 'string', 'max:255'],
            'form' => [
                'type' => 'text',
                'label' => 'Primary location',
                'placeholder' => 'e.g. Austin, TX or United States',
                'wrapper_class' => 'field brand-form__identity-field',
                'fieldset' => 'About the company',
            ],
        ],
        'company_description' => [
            'fillable' => true,
            'rules' => ['nullable', 'string', 'max:4000'],
            'form' => [
                'type' => 'textarea',
                'label' => 'About your company',
                'help' => 'Describe what you offer and why customers choose you.',
                'fieldset' => 'About the company',
            ],
        ],
        'brand_personality_voice' => [
            'fillable' => true,
            'rules' => ['nullable', 'string', 'max:2000'],
            'form' => [
                'type' => 'textarea',
                'label' => 'Brand personality and voice',
                'help' => 'List a few traits the brand should convey and describe how it should sound.',
                'wrapper_class' => 'field brand-form__guidance-field',
                'fieldset' => 'Branding',
            ],
        ],
        'brand_styles_to_avoid' => [
            'fillable' => true,
            'rules' => ['nullable', 'string', 'max:2000'],
            'form' => [
                'type' => 'textarea',
                'label' => 'Styles and tones to avoid',
                'help' => 'List visual styles, personality traits, or tones the brand should never use.',
                'wrapper_class' => 'field brand-form__guidance-field',
                'fieldset' => 'Branding',
            ],
        ],
        'existing_brand_assets' => [
            'fillable' => true,
            'rules' => ['nullable', 'string', 'max:2000'],
            'form' => [
                'type' => 'textarea',
                'label' => 'Existing fonts and brand assets',
                'help' => 'List fonts, alternate logos, photography, brand guides, or other materials we should follow.',
                'fieldset' => 'Branding',
            ],
        ],
        'slogans' => [
            'fillable' => true,
            'rules' => ['nullable', 'string', 'max:2000'],
            'form' => ['type' => 'textarea', 'label' => 'Slogans or taglines', 'help' => 'Optional — one per line.'],
        ],
        'logo' => [
            'fillable' => false,
            'form' => [
                'type' => 'image',
                'label' => 'Logo',
                'view' => 'admin.onboarding.partials.logo-field',
                'fieldset' => 'Branding',
            ],
        ],
        'ideal_customer' => [
            'fillable' => true,
            'rules' => ['nullable', 'string', 'max:2000'],
            'form' => [
                'type' => 'textarea',
                'label' => 'Ideal customer',
                'help' => 'Describe the people or organizations you most want to attract.',
                'wrapper_class' => 'field brand-form__audience-main',
                'fieldset' => 'Customer / target audience',
            ],
        ],
        'region_scope' => [
            'fillable' => true,
            'rules' => ['required', 'in:regional,national'],
            'form' => [
                'type' => 'select',
                'label' => 'Audience reach',
                'options' => ['Regional / local' => self::SCOPE_REGIONAL, 'Countrywide' => self::SCOPE_NATIONAL],
                'wrapper_class' => 'field brand-form__audience-reach',
                'fieldset' => 'Customer / target audience',
            ],
        ],
        'primary_colors' => [
            'fillable' => true,
            'cast' => 'array',
            'form' => false,
        ],
        'primary_color' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'secondary_color' => ['fillable' => true, 'type' => 'string', 'form' => false],

        'logo_disk' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'logo_path' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'inspiration_domains' => ['fillable' => true, 'cast' => 'array', 'form' => false],
        'suggested_sites' => ['fillable' => true, 'cast' => 'array', 'form' => false],
        'generation_submission_id' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'generation_remote_id' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'generation_status' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'generation_stage' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'generation_error' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'generation_result' => ['fillable' => true, 'cast' => 'array', 'form' => false],
        'generation_started_at' => ['fillable' => true, 'type' => 'date', 'cast' => 'datetime', 'form' => false],
        'site_layout_request_id' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'site_layout_remote_id' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'site_layout_status' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'site_layout_error' => ['fillable' => true, 'type' => 'string', 'form' => false],
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

        return Storage::disk($this->logo_disk ?: config('filesystems.default'))->url($this->logo_path);
    }

    public function contentUnlocked(): bool
    {
        return ! $this->generation_remote_id
            || in_array($this->generation_stage, self::CONTENT_UNLOCKED_STAGES, true);
    }
}
