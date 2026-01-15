<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureUsage extends Model
{
    protected $table = 'feature_usage';

    protected $fillable = [
        'feature_name',
        'usage_count',
        'unique_sessions',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public static function track(string $featureName, string $sessionId)
    {
        $record = static::firstOrCreate(
            ['feature_name' => $featureName, 'date' => now()->toDateString()],
            ['usage_count' => 0, 'unique_sessions' => 0]
        );

        $record->increment('usage_count');

        // Track unique sessions (simple check, could be improved with cache)
        if (!session("feature_tracked_{$featureName}_" . now()->toDateString())) {
            $record->increment('unique_sessions');
            session(["feature_tracked_{$featureName}_" . now()->toDateString() => true]);
        }
    }
}
