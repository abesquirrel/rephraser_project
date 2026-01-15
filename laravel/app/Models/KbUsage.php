<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KbUsage extends Model
{
    protected $table = 'kb_usage';

    protected $fillable = [
        'generation_id',
        'kb_entry_id',
        'similarity_score',
        'was_used_in_prompt',
        'rank_position',
    ];

    protected $casts = [
        'was_used_in_prompt' => 'boolean',
    ];

    public function generation()
    {
        return $this->belongsTo(ModelGeneration::class, 'generation_id');
    }

    public function knowledgeBase()
    {
        return $this->belongsTo(KnowledgeBase::class, 'kb_entry_id');
    }
}
