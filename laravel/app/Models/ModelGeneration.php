<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Added this line
use Illuminate\Database\Eloquent\Model;

class ModelGeneration extends Model
{
    use HasFactory; // Added this line

    protected $fillable = [
        'session_id',
        'model_id',
        'input_text_length',
        'output_text_length',
        'completion_tokens',
        'total_tokens',
        'generation_time_ms',
        'was_approved',
        'was_edited',
        'edit_distance',
        'temperature',
        'max_tokens',
        'kb_count',
        'web_search_enabled',
        'template_mode',
        'error_occurred',
        'error_message',
    ];

    protected $casts = [
        'was_approved' => 'boolean',
        'was_edited' => 'boolean',
        'web_search_enabled' => 'boolean',
        'template_mode' => 'boolean',
        'error_occurred' => 'boolean',
    ];

    public function session()
    {
        return $this->belongsTo(UserSession::class, 'session_id', 'session_id');
    }

    public function kbUsages()
    {
        return $this->hasMany(KbUsage::class, 'generation_id');
    }
}
