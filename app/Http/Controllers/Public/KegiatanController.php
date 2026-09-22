<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Kegiatan;
use App\Models\KegiatanPendaftaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class KegiatanController extends Controller
{
    public function index(Request $request): View
    {
        $kegiatan = Kegiatan::where('status', 'dibuka')
            ->when($request->filled('jenis'), fn ($q) => $q->where('jenis', $request->jenis))
            ->orderBy('tanggal_mulai')
            ->paginate(9)
            ->withQueryString();

        return view('landing-page.kegiatan.index', compact('kegiatan'));
    }

    public function show(Kegiatan $kegiatan): View
    {
        abort_unless(in_array($kegiatan->status, ['dibuka', 'ditutup', 'selesai'], true), 404);

        $kegiatan->load('customFields');

        $sudahDaftar = false;
        if (session()->has('anggota')) {
            $sudahDaftar = KegiatanPendaftaran::where('kegiatan_id', $kegiatan->id)
                ->where('anggota_id', session('anggota.id'))
                ->exists();
        }

        return view('landing-page.kegiatan.show', compact('kegiatan', 'sudahDaftar'));
    }

    public function daftar(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        // Sama seperti gate di section Kontak — harus login sebagai anggota dulu.
        if (! session()->has('anggota')) {
            return redirect()
                ->route('anggota.login')
                ->with('error', 'Silakan login sebagai anggota untuk mendaftar kegiatan ini.');
        }

        $kegiatan->load('customFields');

        $rules = [];
        foreach ($kegiatan->customFields as $field) {
            $key = "custom_fields.{$field->id}";

            $fieldRules = [$field->wajib ? 'required' : 'nullable'];

            $fieldRules[] = match ($field->tipe) {
                'number' => 'numeric',
                'checkbox' => 'boolean',
                'select' => Rule::in($field->opsi ?? []),
                default => 'string',
            };

            $rules[$key] = $fieldRules;
        }

        $validated = $request->validate($rules);
        $customValues = $validated['custom_fields'] ?? [];

        try {
            DB::transaction(function () use ($kegiatan, $customValues) {
                // Lock baris kegiatan supaya perhitungan kuota aman dari race
                // condition kalau ada dua anggota submit di waktu yang nyaris sama.
                $kegiatanLocked = Kegiatan::where('id', $kegiatan->id)->lockForUpdate()->first();

                $sudahDaftar = KegiatanPendaftaran::where('kegiatan_id', $kegiatanLocked->id)
                    ->where('anggota_id', session('anggota.id'))
                    ->exists();

                if ($sudahDaftar) {
                    throw ValidationException::withMessages(['pendaftaran' => 'Anda sudah terdaftar di kegiatan ini.']);
                }

                if ($kegiatanLocked->kuota !== null && $kegiatanLocked->kuota_terisi >= $kegiatanLocked->kuota) {
                    throw ValidationException::withMessages(['pendaftaran' => 'Maaf, kuota kegiatan ini baru saja penuh.']);
                }

                KegiatanPendaftaran::create([
                    'kegiatan_id' => $kegiatanLocked->id,
                    'anggota_id' => session('anggota.id'),
                    'status' => 'diterima',
                    'custom_values' => $customValues,
                ]);
            });
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()
            ->route('kegiatan.show', $kegiatan)
            ->with('success', 'Pendaftaran berhasil! Sampai jumpa di kegiatannya.');
    }
}