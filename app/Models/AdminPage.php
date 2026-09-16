<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminPage extends Model
{
    protected $fillable = [
        'slug', 'label', 'route_name', 'icon_svg',
        'type', 'order', 'is_active', 'is_locked',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('order');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}