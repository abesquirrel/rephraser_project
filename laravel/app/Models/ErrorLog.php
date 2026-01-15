<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    protected $fillable = [
        'severity',
        'source',
        'error_code',
        'error_message',
        'stack_trace',
        'user_session_id',
        'request_url',
        'request_method',
        'request_payload',
        'php_version',
        'laravel_version',
        'browser_agent',
        'resolved_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'created_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public $timestamps = false;

    public function session()
    {
        return $this->belongsTo(UserSession::class, 'user_session_id', 'session_id');
    }
}
