<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnggotaRfidPairingController extends Controller
{
    public function index(): View
    {
        return view('anggota.rfid-pairing');
    }

    /**
     * Dipanggil begitu kartu di-tap / kode diketik. Mengecek apakah
     * kode ini sudah terdaftar ke anggota lain atau belum.
     */
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string'],
        ]);

        $kode = $this->normalize($validated['kode']);

        if ($kode === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Kode tidak valid.',
            ], 422);
        }

        $anggota = Anggota::where('rfid_code', $kode)->first();

        if ($anggota) {
            return response()->json([
                'exists' => true,
                'kode' => $kode,
                'anggota' => [
                    'id' => $anggota->id,
                    'nama' => $anggota->nama_lengkap,
                    'nik' => $anggota->nik_karyawan,
                    'departemen' => $anggota->departemen,
                ],
            ]);
        }

        return response()->json([
            'exists' => false,
            'kode' => $kode,
        ]);
    }

    /**
     * Pencarian anggota untuk dipasangkan dengan kode yang baru di-tap.
     * Sama polanya dengan AdminKegiatanPendaftaranController::cariAnggota().
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $anggota = Anggota::query()
            ->where('is_active', 1)
            ->where(function ($query) use ($q) {
                $query->where('nama_lengkap', 'like', "%{$q}%")
                    ->orWhere('nik_karyawan', 'like', "%{$q}%");
            })
            ->orderBy('nama_lengkap')
            ->limit(10)
            ->get(['id', 'nama_lengkap', 'nik_karyawan', 'departemen', 'rfid_code']);

        return response()->json($anggota);
    }

    /**
     * Simpan kode ke anggota terpilih. Kalau kode itu sebelumnya dipakai
     * anggota lain, otomatis dilepas dari anggota lama (dipindah), supaya
     * tidak bentrok dengan constraint unique di kolom rfid_code.
     */
    public function assign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string'],
            'anggota_id' => ['required', 'integer', 'exists:anggota,id'],
        ]);

        $kode = $this->normalize($validated['kode']);

        if ($kode === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Kode tidak valid.',
            ], 422);
        }

        $anggota = Anggota::findOrFail($validated['anggota_id']);

        DB::transaction(function () use ($kode, $anggota) {
            // Lepas kode dari pemilik lama (jika ada dan bukan anggota yang sama)
            Anggota::where('rfid_code', $kode)
                ->where('id', '!=', $anggota->id)
                ->update(['rfid_code' => null]);

            $anggota->update(['rfid_code' => $kode]);
        });

        return response()->json([
            'ok' => true,
            'message' => "Kartu berhasil dipasangkan ke {$anggota->nama_lengkap}.",
            'anggota' => [
                'id' => $anggota->id,
                'nama' => $anggota->nama_lengkap,
                'nik' => $anggota->nik_karyawan,
            ],
        ]);
    }

    /**
     * Sama persis dengan normalisasi yang dipakai di
     * AdminKegiatanPendaftaranController::scan() — buang separator,
     * uppercase — supaya kode dari reader USB dan Web NFC tetap cocok.
     */
    private function normalize(string $kode): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', trim($kode)));
    }
}