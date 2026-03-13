<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiResponseLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'meta_data' => 'array',
        'web_search_enabled' => 'boolean',
        'template_mode' => 'boolean',
        'was_approved' => 'boolean',
        'was_rejected' => 'boolean',
        'was_edited' => 'boolean',
        'is_error' => 'boolean',
    ];
}
