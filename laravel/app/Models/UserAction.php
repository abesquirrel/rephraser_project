<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAction extends Model
{
    protected $fillable = [
        'session_id',
        'action_type',
        'action_details',
        'timestamp',
    ];

    protected $casts = [
        'action_details' => 'array',
        'timestamp' => 'datetime',
    ];

    public $timestamps = false;

    public function session()
    {
        return $this->belongsTo(UserSession::class, 'session_id', 'session_id');
    }
}
