<?php

namespace App\Helpers;

class RoleHelper
{
    /**
     * Normalisasi nama role ke format standar (Title Case)
     */
    public static function normalize(string $role): string
    {
        $role = trim($role);
        
        // Mapping untuk role yang umum
        $mapping = [
            'sekretaris'                => 'Sekretaris',
            'ketua'                     => 'Ketua',
            'bendahara'                 => 'Bendahara',
            'wakil ketua bidang 1'      => 'Wakil Ketua Bidang 1',
            'wakil sekretaris bidang 1' => 'Wakil sekretari Bidang 1',
            'wakil ketua bidang 2'      => 'Wakil Ketua Bidang 2',
            'wakil sekretaris bidang 2' => 'Wakil sekretari Bidang 2',
            'wakil ketua bidang 3'      => 'Wakil Ketua Bidang 3',
            'wakil sekretaris bidang 3' => 'Wakil sekretari Bidang 3',
            'wakil ketua bidang 4'      => 'Wakil Ketua Bidang 4',
            'wakil sekretaris bidang 4' => 'Wakil sekretari Bidang 4',
            'wakil ketua bidang 5'      => 'Wakil Ketua Bidang 5',
            'wakil sekretaris bidang 5' => 'Wakil sekretari Bidang 5',
            'wakil ketua bidang 6'      => 'Wakil Ketua Bidang 6',
            'wakil sekretaris bidang 6' => 'Wakil sekretari Bidang 6',
            'pangkorlap'                => 'PANGKORLAP',
            'sekretaris pangkorlap'     => 'Sekretaris PANGKORLAP',
            'pangkormi'                 => 'PANGKORMI',
            'sekretaris pangkormi'      => 'Sekretaris PANGKORMI',

        ];
        
        $lower = strtolower($role);
        
        // Cek apakah ada di mapping
        if (isset($mapping[$lower])) {
            return $mapping[$lower];
        }
        
        // Jika tidak ada di mapping, return dengan Title Case
        return ucwords(strtolower($role));
    }
    
    /**
     * Cek apakah dua role sama (case-insensitive)
     */
    public static function isSameRole(string $role1, string $role2): bool
    {
        return strtolower(trim($role1)) === strtolower(trim($role2));
    }
    
    /**
     * Cek apakah role termasuk dalam daftar yang diizinkan (case-insensitive)
     */
    public static function isInRoles(string $role, array $allowedRoles): bool
    {
        $role = strtolower(trim($role));
        $allowed = array_map(fn($r) => strtolower(trim($r)), $allowedRoles);
        return in_array($role, $allowed, true);
    }
}