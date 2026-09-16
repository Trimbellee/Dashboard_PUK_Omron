<?php

namespace App\Http\Controllers;

use App\Models\InventarisPeminjaman;
use App\Models\InventarisBarang;
use Illuminate\Http\Request;

class InventarisPeminjamanController extends Controller
{
    // Halaman utama peminjaman
    public function index()
    {
        $peminjaman = InventarisPeminjaman::with(['barang', 'petugas', 'penerima'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Statistik
        $statistik = [
            'total' => InventarisPeminjaman::count(),
            'aktif' => InventarisPeminjaman::where('status', 'aktif')->count(),
            'selesai' => InventarisPeminjaman::where('status', 'selesai')->count(),
            'terlambat' => InventarisPeminjaman::where('status', 'terlambat')->count(),
        ];

        return view('inventaris.peminjaman', compact('peminjaman', 'statistik'));
    }

    // Filter peminjaman by status (via AJAX)
    public function filter($status = null)
    {
        $query = InventarisPeminjaman::with(['barang', 'petugas', 'penerima']);

        if ($status && $status !== 'semua') {
            $query->where('status', $status);
        }

        $peminjaman = $query->orderBy('created_at', 'desc')->get();

        if (request()->ajax()) {
            return response()->json($peminjaman);
        }

        return view('inventaris.peminjaman', compact('peminjaman'));
    }

    // Detail peminjaman
    public function show($id)
    {
        $peminjaman = InventarisPeminjaman::with(['barang', 'petugas', 'penerima'])->findOrFail($id);
        return view('inventaris.peminjaman-detail', compact('peminjaman'));
    }

    // API untuk riwayat peminjaman
    public function riwayat()
    {
        $riwayat = InventarisPeminjaman::with(['barang', 'petugas', 'penerima'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        if (request()->ajax()) {
            return response()->json($riwayat);
        }

        return view('inventaris.riwayat', compact('riwayat'));
    }
}
