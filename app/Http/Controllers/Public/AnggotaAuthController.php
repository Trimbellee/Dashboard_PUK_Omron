<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AnggotaAuthController extends Controller
{
    /**
     * Nama kolom NIK di tabel users.
     */
    protected string $kolomNikDiUsers = 'nik_karyawan';

    /**
     * Key session sementara: anggota sudah lolos verifikasi password,
     * tapi masih memakai password default dan wajib menggantinya.
     * Selama key ini yang aktif, session('anggota') BELUM dibuat.
     */
    protected string $sessionGantiPassword = 'anggota_ganti_password';

    /**
     * Tampilkan form login anggota (landing page).
     * Terpisah dari /login yang dipakai untuk dashboard PUK (tabel users).
     */
    public function showLogin(): View|RedirectResponse
    {
        if (session()->has('anggota')) {
            return redirect()->route('landing.index');
        }

        if (session()->has($this->sessionGantiPassword)) {
            return redirect()->route('anggota.password.ganti');
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

        // Cegah session fixation.
        $request->session()->regenerate();

        // Password default (nama lengkap + "_" + NIK) -> wajib ganti password dulu.
        if ($this->sama_dengan_default($anggota, $validated['password'])) {
            $request->session()->forget('anggota');
            $request->session()->put($this->sessionGantiPassword, $anggota->id);

            return redirect()->route('anggota.password.ganti')
                ->with('info', 'Demi keamanan, silakan ganti password default Anda terlebih dahulu.');
        }

        $this->mulaiSesiAnggota($anggota);

        return redirect()->route('landing.index')
            ->with('success', 'Selamat datang, ' . $anggota->nama_lengkap . '.');
    }

    /**
     * Halaman wajib ganti password (hanya bisa diakses setelah login dengan password default).
     */
    public function showGantiPassword(Request $request): View|RedirectResponse
    {
        $anggota = $this->anggotaMenungguGantiPassword($request);

        if (! $anggota) {
            return redirect()->route('anggota.login')
                ->with('error', 'Sesi berakhir, silakan login kembali.');
        }

        return view('landing-page.auth.ganti-password', [
            'anggota'         => $anggota,
            'passwordDefault' => $this->passwordDefault($anggota),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $anggota = $this->anggotaMenungguGantiPassword($request);

        if (! $anggota) {
            return redirect()->route('anggota.login')
                ->with('error', 'Sesi berakhir, silakan login kembali.');
        }

        $request->validate([
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->numbers(),
                function (string $attribute, mixed $value, \Closure $fail) use ($anggota) {
                    if ($this->sama_dengan_default($anggota, (string) $value)) {
                        $fail('Password baru tidak boleh sama dengan password default.');
                    }
                },
            ],
        ], [
            'password.required'  => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'password.min'       => 'Password minimal 8 karakter.',
        ]);

        $anggota->password = Hash::make($request->input('password'));
        $anggota->save();

        $request->session()->forget($this->sessionGantiPassword);
        $request->session()->regenerate();

        $this->mulaiSesiAnggota($anggota);

        return redirect()->route('landing.index')
            ->with('success', 'Password berhasil diubah. Selamat datang, ' . $anggota->nama_lengkap . '.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['anggota', $this->sessionGantiPassword]);

        return redirect()->route('landing.index')
            ->with('success', 'Anda berhasil keluar.');
    }

    /**
     * Format password default akun baru: nama lengkap + "_" + NIK karyawan.
     */
    protected function passwordDefault(Anggota $anggota): string
    {
        return $anggota->nama_lengkap . '_' . $anggota->nik_karyawan;
    }

    /**
     * Apakah $input sama dengan password default anggota ini?
     * Toleran: huruf besar/kecil dan spasi berlebih diabaikan.
     * Format lama (password = NIK saja) juga dianggap default.
     */
    protected function sama_dengan_default(Anggota $anggota, string $input): bool
    {
        $input = $this->normalisasi($input);

        return hash_equals($this->normalisasi($this->passwordDefault($anggota)), $input)
            || hash_equals($this->normalisasi((string) $anggota->nik_karyawan), $input);
    }

    protected function normalisasi(string $teks): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($teks)));
    }

    /**
     * Ambil anggota yang sedang menunggu ganti password dari session sementara.
     */
    protected function anggotaMenungguGantiPassword(Request $request): ?Anggota
    {
        $id = $request->session()->get($this->sessionGantiPassword);

        if (! $id) {
            return null;
        }

        return Anggota::where('id', $id)->where('is_active', true)->first();
    }

    /**
     * Bentuk session('anggota') — dipakai login normal & setelah ganti password.
     */
    protected function mulaiSesiAnggota(Anggota $anggota): void
    {
        // Cek apakah NIK ini juga terdaftar sebagai user dashboard (tabel users).
        $punyaAksesDashboard = DB::table('users')
            ->where($this->kolomNikDiUsers, $anggota->nik_karyawan)
            ->exists();

        session([
            'anggota' => [
                'id'              => $anggota->id,
                'nama'            => $anggota->nama_lengkap,
                'nik_karyawan'    => $anggota->nik_karyawan,
                'jabatan_puk'     => $anggota->jabatan_puk,
                'punya_dashboard' => $punyaAksesDashboard,
                'foto_url'        => $anggota->foto_url,
                'email'           => $anggota->email,
            ],
        ]);
    }
}