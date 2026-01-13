<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'action',
        'original_content',
        'rephrased_content',
        'user_name',
        'model_used',
        'latency_ms',
        'temperature',
        'max_tokens',
        'top_p',
        'frequency_penalty',
        'presence_penalty'
    ];
}
