<?php

namespace App\Helpers;

use App\Models\RolePermission;

class PermissionHelper
{
    /**
     * Cek apakah role memiliki permission tertentu.
     * Jika role null (belum login), otomatis false.
     * Case-insensitive.
     */
    public static function can(string $permission, ?string $role = null): bool
    {
        $role = $role ?: session('anggota.jabatan_puk');

        if (is_null($role)) {
            return false;
        }

        // Normalize: trim dan lowercase untuk case-insensitive
        $role = strtolower(trim($role));
        $permission = strtolower(trim($permission));

        return RolePermission::hasPermission($role, $permission);
    }

    /**
     * Cek apakah user memiliki salah satu dari daftar permission
     * Case-insensitive.
     */
    public static function canAny(array $permissions, ?string $role = null): bool
    {
        $role = $role ?: session('anggota.jabatan_puk');

        if (is_null($role)) {
            return false;
        }

        $role = strtolower(trim($role));

        foreach ($permissions as $perm) {
            if (RolePermission::hasPermission($role, strtolower(trim($perm)))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Cek apakah user memiliki semua permission yang diberikan
     * Case-insensitive.
     */
    public static function canAll(array $permissions, ?string $role = null): bool
    {
        $role = $role ?: session('anggota.jabatan_puk');

        if (is_null($role)) {
            return false;
        }

        $role = strtolower(trim($role));

        foreach ($permissions as $perm) {
            if (!RolePermission::hasPermission($role, strtolower(trim($perm)))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Ambil semua permission yang dimiliki role tertentu
     * Case-insensitive - return permission names as stored in DB
     */
    public static function getPermissions(?string $role = null): array
    {
        $role = $role ?: session('anggota.jabatan_puk');

        if (is_null($role)) {
            return [];
        }

        $role = strtolower(trim($role));
        
        // Get all permissions and filter by role (case-insensitive)
        $allPermissions = RolePermission::all();
        $result = [];
        
        foreach ($allPermissions as $rp) {
            if (strtolower(trim($rp->role)) === $role) {
                $result[] = $rp->permission;
            }
        }
        
        return $result;
    }
}