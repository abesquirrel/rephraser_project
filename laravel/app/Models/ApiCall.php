<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiCall extends Model
{
    protected $fillable = [
        'service',
        'endpoint',
        'method',
        'request_payload_size',
        'request_timestamp',
        'response_status',
        'response_time_ms',
        'response_payload_size',
        'estimated_cost_usd',
        'tokens_used',
        'is_error',
        'error_type',
        'error_message',
    ];

    protected $casts = [
        'request_timestamp' => 'datetime',
        'is_error' => 'boolean',
    ];

    public $timestamps = false;
}
