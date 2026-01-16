<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    protected $fillable = [
        'original_text',
        'rephrased_text',
        'keywords',
        'is_template',
        'category',
        'model_used',
        'latency_ms',
        'temperature',
        'max_tokens',
        'top_p',
        'frequency_penalty',
        'presence_penalty',
        'hits',
        'last_used_at',
        'embedding',
        'role'
    ];
}
