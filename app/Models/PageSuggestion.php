<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Keystone\Toolkit\Models\AppModel;

class PageSuggestion extends AppModel
{
    public const STATUS_SUGGESTED = 'suggested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const TASK_SITEMAP = 'sitemap';

    protected $properties = [
        'user_id' => ['fillable' => true, 'type' => 'integer', 'form' => false],
        'parent_id' => ['fillable' => true, 'type' => 'integer', 'form' => false],
        'title' => ['fillable' => true, 'label' => 'Title', 'rules' => ['required', 'string', 'max:255']],
        'slug' => ['fillable' => true, 'label' => 'Slug', 'rules' => ['required', 'string', 'max:255']],
        'summary' => ['fillable' => true, 'label' => 'Summary', 'rules' => ['required', 'string']],
        'rationale' => ['fillable' => true, 'label' => 'Rationale', 'rules' => ['nullable', 'string']],
        'source_asset_ids' => ['fillable' => true, 'cast' => 'array', 'form' => false],
        'suggested_copy' => ['fillable' => true, 'cast' => 'array', 'form' => false],
        'status' => ['fillable' => true, 'label' => 'Status', 'rules' => ['required', 'string']],
        'sort_order' => ['fillable' => true, 'type' => 'integer', 'form' => false],
        'rejection_feedback' => ['fillable' => true, 'label' => 'Denial feedback', 'rules' => ['nullable', 'string', 'max:2000']],
        'reviewed_at' => ['fillable' => true, 'type' => 'date', 'cast' => 'datetime', 'form' => false],
        'ai_model' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'ai_task' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'ai_feedback' => ['fillable' => true, 'cast' => 'boolean', 'form' => false],
        'ai_feedback_at' => ['fillable' => true, 'type' => 'date', 'cast' => 'datetime', 'form' => false],
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }
}
