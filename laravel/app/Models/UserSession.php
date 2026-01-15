<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'started_at',
        'ended_at',
        'total_generations',
        'total_approvals',
        'total_edits',
        'total_web_searches',
        'avg_generation_time_ms',
        'theme',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actions()
    {
        return $this->hasMany(UserAction::class, 'session_id', 'session_id');
    }

    public function generations()
    {
        return $this->hasMany(ModelGeneration::class, 'session_id', 'session_id');
    }
}
