<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromptRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'identity',
        'protocol',
        'format',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    // Ensure only one default role exists
    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->is_default) {
                // Set all other roles to not default
                static::where('id', '!=', $model->id)->update(['is_default' => false]);
            }
        });
    }
}
