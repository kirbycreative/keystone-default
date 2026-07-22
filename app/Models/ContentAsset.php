<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Keystone\Toolkit\Models\AppModel;

class ContentAsset extends AppModel
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';

    protected $properties = [
        'user_id' => ['fillable' => true, 'type' => 'integer', 'form' => false],
        'title' => ['fillable' => true, 'label' => 'Title', 'rules' => ['nullable', 'string', 'max:255']],
        'type' => ['fillable' => true, 'label' => 'Asset type', 'rules' => ['required', 'string']],
        'notes' => ['fillable' => true, 'label' => 'Notes for ingestion', 'rules' => ['nullable', 'string', 'max:2000']],
        'disk' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'path' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'original_filename' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'mime_type' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'size' => ['fillable' => true, 'type' => 'integer', 'form' => false],
        'ingestion_status' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'ingestion_result' => ['fillable' => true, 'cast' => 'array', 'form' => false],
        'remote_id' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'remote_request_id' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'remote_status' => ['fillable' => true, 'type' => 'string', 'form' => false],
        'remote_error' => ['fillable' => true, 'type' => 'string', 'form' => false],
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function types(): array
    {
        return [
            'menu' => 'Menu',
            'promotion' => 'Promotion',
            'advertisement' => 'Advertisement',
            'brand' => 'Brand asset',
            'photo' => 'Photo',
            'document' => 'Document',
            'other' => 'Other',
        ];
    }

    protected function formattedSize(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->size === null) {
                return 'Unknown';
            }

            if ($this->size < 1024) {
                return $this->size . ' B';
            }

            if ($this->size < 1024 * 1024) {
                return round($this->size / 1024, 1) . ' KB';
            }

            return round($this->size / 1024 / 1024, 1) . ' MB';
        });
    }
}
