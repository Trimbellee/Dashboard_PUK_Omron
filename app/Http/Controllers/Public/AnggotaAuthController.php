<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AnggotaAuthController extends Controller
{
    /**
     * Nama kolom NIK di tabel users.
     */
    protected string $kolomNikDiUsers = 'nik_karyawan';

    /**
     * Tampilkan form login anggota (landing page).
     * Terpisah dari /login yang dipakai untuk dashboard PUK (tabel users).
     */
    public function showLogin(): View|RedirectResponse
    {
        if (session()->has('anggota')) {
            return redirect()->route('landing.index');
        }

        return view('landing-page.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nik_karyawan' => ['required', 'string', 'max:8'],
            'password'     => ['required', 'string'],
        ]);

        $anggota = Anggota::where('nik_karyawan', $validated['nik_karyawan'])
            ->where('is_active', true)
            ->first();

        if (! $anggota || ! Hash::check($validated['password'], $anggota->password)) {
            return back()
                ->withInput($request->only('nik_karyawan'))
                ->with('error', 'NIK atau password salah, atau akun tidak aktif.');
        }

        // Cek apakah NIK ini juga terdaftar sebagai user dashboard (tabel users).
        $punyaAksesDashboard = DB::table('users')
            ->where($this->kolomNikDiUsers, $anggota->nik_karyawan)
            ->exists();

        session([
            'anggota' => [
                'id'                => $anggota->id,
                'nama'              => $anggota->nama_lengkap,
                'nik_karyawan'      => $anggota->nik_karyawan,
                'jabatan_puk'       => $anggota->jabatan_puk,
                'punya_dashboard'   => $punyaAksesDashboard,
                'foto_url'          => $anggota->foto_url,
                'email'             => $anggota->email,
            ],
        ]);

        return redirect()->route('landing.index')
            ->with('success', 'Selamat datang, ' . $anggota->nama_lengkap . '.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('anggota');

        return redirect()->route('landing.index')
            ->with('success', 'Anda berhasil keluar.');
    }
}