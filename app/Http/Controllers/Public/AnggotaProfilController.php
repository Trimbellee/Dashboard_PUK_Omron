<?php

namespace App\Http\Controllers\Public;

use App\Helpers\SeragamHelper;
use App\Http\Controllers\Controller;
use App\Models\Anggota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Intervention\Image\Laravel\Facades\Image;
use Laravolt\Indonesia\Models\Province;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Response;

class AnggotaProfilController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $anggota = Anggota::find(session('anggota.id'));

        if (! $anggota) {
            session()->forget('anggota');

            return redirect()
                ->route('anggota.login')
                ->with('error', 'Data anggota tidak ditemukan. Silakan login kembali.');
        }

        $wilayahDomisili = $this->resolveWilayahNames(
            $anggota->provinsi_domisili,
            $anggota->kota_domisili,
            $anggota->kecamatan_domisili,
            $anggota->kelurahan_domisili
        );

        $wilayahKtp = $this->resolveWilayahNames(
            $anggota->provinsi_ktp,
            $anggota->kota_ktp,
            $anggota->kecamatan_ktp,
            $anggota->kelurahan_ktp
        );

        return view('landing-page.anggota.profil', compact('anggota', 'wilayahDomisili', 'wilayahKtp'));
    }

    public function qrCode()
    {
        $anggota = Anggota::find(session('anggota.id'));

        if (! $anggota) {
            abort(404);
        }

        $qrCode = new QrCode(
            data: $anggota->ulid,
            size: 300,
            margin: 10,
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return response($result->getString(), 200)
            ->header('Content-Type', $result->getMimeType())
            ->header('Cache-Control', 'no-store');
    }


    public function edit(): View|RedirectResponse
    {
        $anggota = Anggota::find(session('anggota.id'));

        if (! $anggota) {
            session()->forget('anggota');

            return redirect()
                ->route('anggota.login')
                ->with('error', 'Data anggota tidak ditemukan. Silakan login kembali.');
        }

        $provinsiList = Province::orderBy('name')->get(['code', 'name']);

        return view('landing-page.anggota.profil-edit', compact('anggota', 'provinsiList'));
    }

    public function update(Request $request): RedirectResponse
    {
        $anggota = Anggota::find(session('anggota.id'));

        if (! $anggota) {
            session()->forget('anggota');

            return redirect()
                ->route('anggota.login')
                ->with('error', 'Data anggota tidak ditemukan. Silakan login kembali.');
        }

        $validated = $request->validate([
            'jenis_identitas'       => 'nullable|in:KTP,SIM,PASPOR',
            'nomor_identitas'       => 'nullable|regex:/^\d+$/|max:20',
            'nomor_whatsapp'        => [
                'required',
                'regex:/^\+?[0-9]{10,13}$/',
                'unique:anggota,nomor_whatsapp,' . $anggota->id,
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:anggota,email,' . $anggota->id,
            ],
            'tempat_lahir'          => 'nullable|max:100',
            'tanggal_lahir'         => 'nullable|date',
            'tanggal_mulai_bekerja' => 'nullable|date',
            'pendidikan_terakhir'   => 'nullable|in:SD,SMP,SMA,SMK,D1,D2,D3,D4,S1,S2,S3',
            // Anggota hanya boleh mengisi/mengubah UKURAN. Status "sudah punya"
            // sengaja tidak divalidasi di sini → tidak ikut tersimpan (diatur admin).
            'ukuran_kaos'           => 'nullable|in:' . implode(',', SeragamHelper::UKURAN),
            'ukuran_kemeja'         => 'nullable|in:' . implode(',', SeragamHelper::UKURAN),
            'provinsi_domisili'     => 'nullable',
            'kota_domisili'         => 'nullable',
            'kecamatan_domisili'    => 'nullable',
            'kelurahan_domisili'    => 'nullable',
            'alamat_domisili'       => 'nullable',
            'provinsi_ktp'          => 'nullable',
            'kota_ktp'              => 'nullable',
            'kecamatan_ktp'         => 'nullable',
            'kelurahan_ktp'         => 'nullable',
            'alamat_ktp'            => 'nullable',
            'foto'                  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'password_lama'         => 'nullable|required_with:password|string',
            'password'              => 'nullable|string|min:8|confirmed',
        ]);

        // Validasi manual: password lama harus cocok kalau anggota ingin ganti password.
        if (! empty($validated['password'])) {
            if (! Hash::check($request->input('password_lama'), $anggota->password)) {
                return back()
                    ->withErrors(['password_lama' => 'Password lama yang Anda masukkan salah.'])
                    ->withInput();
            }
        }

        if ($request->hasFile('foto')) {
            $validated['foto'] = $this->uploadFoto($request->file('foto'), $anggota->nik_karyawan);
        }

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        unset($validated['password_confirmation'], $validated['password_lama']);

        $anggota->update($validated);

        $anggotaFresh = $anggota->fresh();

        session([
            'anggota' => array_merge(session('anggota'), [
                'nama'     => $anggotaFresh->nama_lengkap,
                'foto_url' => $anggotaFresh->foto_url,
                'email'    => $anggotaFresh->email,
            ]),
        ]);

        return redirect()
            ->route('anggota.profil')
            ->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * Resolve kode wilayah (provinsi/kota/kecamatan/kelurahan) menjadi nama.
     * Query langsung ke tabel indonesia_* berdasarkan kolom `code`, sama seperti
     * yang dipakai AnggotaController::findWilayahIds() saat import.
     */
    private function resolveWilayahNames(
        ?string $provinsiCode,
        ?string $kotaCode,
        ?string $kecamatanCode,
        ?string $kelurahanCode
    ): array {
        return [
            'provinsi' => $provinsiCode
                ? DB::table('indonesia_provinces')->where('code', $provinsiCode)->value('name')
                : null,
            'kota' => $kotaCode
                ? DB::table('indonesia_cities')->where('code', $kotaCode)->value('name')
                : null,
            'kecamatan' => $kecamatanCode
                ? DB::table('indonesia_districts')->where('code', $kecamatanCode)->value('name')
                : null,
            'kelurahan' => $kelurahanCode
                ? DB::table('indonesia_villages')->where('code', $kelurahanCode)->value('name')
                : null,
        ];
    }

    /**
     * Sama persis dengan AnggotaController::uploadFoto() — disalin di sini
     * supaya controller ini tidak bergantung ke controller admin.
     */
    private function uploadFoto($file, string $nik): string
    {
        $folder = storage_path('app/public/anggota');

        if (! File::exists($folder)) {
            File::makeDirectory($folder, 0755, true);
        }

        $filename = $nik . '.webp';
        $path     = $folder . '/' . $filename;

        if (File::exists($path)) {
            File::delete($path);
        }

        Image::read($file)
            ->scaleDown(width: 800)
            ->toWebp(95)
            ->save($path);

        return 'anggota/' . $filename;
    }
}