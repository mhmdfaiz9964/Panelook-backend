<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'action', 'module', 'record_label', 'ip_address', 'before', 'after'];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, string $module, ?string $recordLabel = null, ?array $before = null, ?array $after = null, $userId = null, $ip = null)
    {
        return static::create([
            'user_id' => $userId,
            'action' => $action,
            'module' => $module,
            'record_label' => $recordLabel,
            'ip_address' => $ip,
            'before' => $before,
            'after' => $after,
        ]);
    }
}
