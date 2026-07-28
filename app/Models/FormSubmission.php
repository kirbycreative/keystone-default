<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormSubmission extends Model
{
    protected $fillable = ['page_path', 'section_id', 'payload'];

    protected function casts(): array
    {
        return ['payload' => 'encrypted:array'];
    }
}
