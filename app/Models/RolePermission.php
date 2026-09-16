<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $fillable = ['role', 'permission'];

    public static function getRolesForPermission(string $permission): array
    {
        return self::where('permission', $permission)->pluck('role')->toArray();
    }

    /**
     * Cek apakah role memiliki permission tertentu.
     * Case-insensitive.
     */
    public static function hasPermission(string $role, string $permission): bool
    {
        return self::whereRaw('LOWER(role) = ?', [strtolower(trim($role))])
            ->whereRaw('LOWER(permission) = ?', [strtolower(trim($permission))])
            ->exists();
    }

    public static function getPermissionsForRole(string $role): array
    {
        return self::whereRaw('LOWER(role) = ?', [strtolower(trim($role))])
            ->pluck('permission')
            ->toArray();
    }
}