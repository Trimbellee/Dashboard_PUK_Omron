<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KontakController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $redirectUrl = url()->previous() . '#kontak';

        $sessionAnggota = session('anggota');

        if (!$sessionAnggota || empty($sessionAnggota['id'])) {
            return redirect()->route('anggota.login')
                ->with('error', 'Silakan login terlebih dahulu untuk mengirim pesan.');
        }

        $anggota = Anggota::find($sessionAnggota['id']);

        if (!$anggota || !$anggota->email) {
            return redirect($redirectUrl)
                ->with('error', 'Akun Anda belum memiliki email terdaftar. Silakan hubungi pengurus untuk memperbarui data sebelum mengirim pesan.');
        }

        $validator = validator($request->all(), [
            'pesan' => ['required', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return redirect($redirectUrl)
                ->withErrors($validator)
                ->withInput();
        }

        Contact::create([
            'anggota_id'   => $anggota->id,
            'nama'         => $anggota->nama_lengkap,
            'nik_karyawan' => $anggota->nik_karyawan,
            'departemen'   => $anggota->departemen,
            'email'        => $anggota->email,
            'pesan'        => $validator->validated()['pesan'],
        ]);

        return redirect($redirectUrl)
            ->with('success', 'Pesan Anda berhasil dikirim. Pengurus akan segera menindaklanjuti.');
    }
}