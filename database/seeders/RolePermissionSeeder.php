<?php

namespace Database\Seeders;

use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // Sekretaris: semua
            'Sekretaris' => [
                'create_news', 'create_gallery',
                'review_news', 'review_gallery',
                'approve_news', 'approve_gallery',
                'access_admin'
            ],
            // Wakil Ketua Bidang 5: creator + reviewer
            'Wakil Ketua Bidang 5' => [
                'create_news', 'create_gallery',
                'review_news', 'review_gallery'
            ],
            // Wakil Sekretaris Bidang 5: creator + reviewer
            'Wakil Sekretaris Bidang 5' => [
                'create_news', 'create_gallery',
                'review_news', 'review_gallery'
            ],
            // Tim Media: hanya creator
            'Tim Media' => [
                'create_news', 'create_gallery'
            ],
        ];

        foreach ($permissions as $role => $perms) {
            foreach ($perms as $perm) {
                RolePermission::firstOrCreate([
                    'role' => $role,
                    'permission' => $perm,
                ]);
            }
        }
    }
}