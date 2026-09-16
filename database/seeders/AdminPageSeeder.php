<?php

// database/seeders/AdminPageSeeder.php

namespace Database\Seeders;

use App\Models\AdminPage;
use Illuminate\Database\Seeder;

class AdminPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'berita', 'label' => 'Berita', 'route_name' => 'admin.berita.index',
                'order' => 10,
                'icon_svg' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M8 9h8M8 13h5"/></svg>',
            ],
            [
                'slug' => 'galeri', 'label' => 'Galeri', 'route_name' => 'admin.galeri.index',
                'order' => 20,
                'icon_svg' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>',
            ],
            [
                'slug' => 'medsos', 'label' => 'Media Sosial', 'route_name' => 'admin.medsos.index',
                'order' => 30,
                'icon_svg' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8z"/></svg>',
            ],
            [
                'slug' => 'informasi', 'label' => 'Informasi', 'route_name' => 'admin.informasi.index',
                'order' => 40,
                'icon_svg' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>',
            ],
            [
                'slug' => 'profile', 'label' => 'Visi & Misi', 'route_name' => 'admin.profile.index',
                'order' => 50,
                'icon_svg' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 4 5v6c0 5 3.5 8.5 8 11 4.5-2.5 8-6 8-11V5l-8-3z"/></svg>',
            ],
            [
                'slug' => 'struktur', 'label' => 'Struktur', 'route_name' => 'admin.struktur.index',
                'order' => 60,
                'icon_svg' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 4 5v6c0 5 3.5 8.5 8 11 4.5-2.5 8-6 8-11V5l-8-3z"/></svg>',
            ],
        ];

        foreach ($pages as $page) {
            AdminPage::updateOrCreate(['slug' => $page['slug']], $page);
        }
    }
}