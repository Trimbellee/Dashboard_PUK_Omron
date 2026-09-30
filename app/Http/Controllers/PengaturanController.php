<?php

namespace App\Http\Controllers;

class PengaturanController extends Controller
{
    public function index()
    {
        $menus = [
            [
                'title'       => 'Insentif',
                'description' => 'Atur persentase / nominal insentif anggota.',
                'icon'        => '💸',
                'route'       => 'insentif.index',
            ],
            [
                'title'       => 'Setting Kas',
                'description' => 'Konfigurasi saldo awal kas, distribusi COS, dan lainnya.',
                'icon'        => '🏦',
                'route'       => 'kas.index',
            ],

            [
                'title'       => 'Setting Bidang',
                'description' => 'Setting Alokasi dana bidang.',
                'icon'        => '🏦',
                'route'       => 'bidang.index',
            ],
            // tambahkan sub-pengaturan lain di sini nanti
        ];

        return view('pengaturan.index', compact('menus'));
    }
}