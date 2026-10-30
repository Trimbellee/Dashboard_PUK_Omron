<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Daftarkan halaman "Pengambilan Seragam" ke menu admin (tabel admin_pages).
     *
     * Slug WAJIB sama dengan segmen ke-2 nama route (admin.pengambilan-seragam.*),
     * karena middleware check.page.access mencocokkannya ke admin_pages.slug.
     * Slug yang tidak terdaftar akan DILEWATKAN oleh middleware (tanpa cek izin),
     * jadi pendaftaran ini sekaligus yang mengaktifkan penjagaan akses halamannya.
     *
     * Setelah migrate: beri izin halaman ini ke peran yang bertugas di sekretariat
     * lewat menu "Peran & Izin". Sebelum itu belum ada peran yang bisa membukanya.
     */
    public function up(): void
    {
        if (DB::table('admin_pages')->where('slug', 'pengambilan-seragam')->exists()) {
            return;
        }

        DB::table('admin_pages')->insert([
            'slug'       => 'pengambilan-seragam',
            'label'      => 'Pengambilan Seragam',
            'route_name' => 'admin.pengambilan-seragam.index',
            'icon_svg'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/></svg>',
            'type'       => 'module',
            'order'      => ((int) DB::table('admin_pages')->max('order')) + 1,
            'is_active'  => true,
            // Terkunci: kalau barisnya terhapus, penjagaan izin halaman ikut hilang (lihat catatan di atas).
            'is_locked'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('role_permissions')->where('permission', 'pengambilan-seragam')->delete();
        DB::table('admin_pages')->where('slug', 'pengambilan-seragam')->delete();
    }
};