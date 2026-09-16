<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'loggable_id', 'loggable_type',
        'user_id', 'action', 'icon', 'description', 'properties'
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function loggable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(Anggota::class, 'user_id');
    }

    public static function log($loggable, $action, $description = null, $properties = [])
    {
        $icons = [
            'created' => '📝',
            'updated' => '✏️',
            'deleted' => '🗑️',
            'submitted' => '📤',
            'reviewed' => '✅',
            'approved' => '✅',
            'rejected' => '❌',
            'published' => '🚀',
            'takedown' => '🔒',
            'restored' => '↩️',
        ];

        return self::create([
            'loggable_id' => $loggable->id,
            'loggable_type' => get_class($loggable),
            'user_id' => session('anggota.id'),
            'action' => $action,
            'icon' => $icons[$action] ?? '📌',
            'description' => $description,
            'properties' => $properties,
        ]);
    }
}