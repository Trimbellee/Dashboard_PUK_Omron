<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\PengambilanSeragam;
use App\Models\TotalKaryawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Laravolt\Indonesia\Models\Province;
use App\Exports\AnggotaAktifExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use App\Helpers\RfidHelper;
use App\Helpers\SeragamHelper;



class AnggotaController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LIST DATA
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $search = $request->search;

        $sort = $request->get('sort', 'nama_lengkap');
        $dir  = $request->get('dir', 'asc');

        $allowedSort = [
            'nama_lengkap',
            'departemen',
            'jabatan',
            'nik_karyawan',
            'status_karyawan'
        ];

        if (!in_array($sort, $allowedSort)) {
            $sort = 'nama_lengkap';
        }

        if (!in_array($dir, ['asc', 'desc'])) {
            $dir = 'asc';
        }

        $perPage = (int) $request->get('per_page', 20);

        if (!in_array($perPage, [10, 20, 50, 100])) {
            $perPage = 20;
        }

        $anggota = Anggota::query()
            ->where('is_active', 1)

            ->when($search, function ($q) use ($search) {
                $q->where(function ($x) use ($search) {
                    $x->where('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('nik_karyawan', 'like', "%{$search}%")
                        ->orWhere('departemen', 'like', "%{$search}%")
                        ->orWhere('jabatan', 'like', "%{$search}%");
                });
            })

            ->orderBy($sort, $dir)

            ->paginate($perPage)
            ->withQueryString();

        if ($request->ajax()) {
            return view('anggota._table', compact(
                'anggota',
                'sort',
                'dir',
                'perPage'
            ))->render();
        }

        return view('anggota.index', compact(
            'anggota',
            'search',
            'sort',
            'dir',
            'perPage'
        ));
    }
    /*
    |--------------------------------------------------------------------------
    | FORM CREATE
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        $provinsiList = Province::orderBy('name')->get(['code', 'name']);

        

        // Buat instance kosong agar blade tidak error saat akses $anggota->xxx
        $anggota = new Anggota();

        return view('anggota.create', compact(
            'provinsiList',
            'anggota'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | SIMPAN DATA
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_lengkap'          => 'required|max:255',
            'nik_karyawan'          => 'required|digits:8|unique:anggota,nik_karyawan',
            'departemen'            => 'required',
            'jabatan'               => 'required',
            'jenis_kelamin'         => 'required|in:Pria,Wanita',
            'status_karyawan'       => 'required',
            'tanggal_mulai_bekerja' => 'nullable|date',
            'jenis_identitas'       => 'nullable',
            'nomor_identitas'       => 'nullable|regex:/^\d+$/',
            'nomor_whatsapp'        => [
                                        'required',
                                        'regex:/^\+?[0-9]{10,13}$/',
                                        'unique:anggota,nomor_whatsapp'
                                        ],
            'email'                 => [
                                        'required',
                                        'email',
                                        'max:255',
                                        'unique:anggota,email'
                                        ],
            'tempat_lahir'          => 'nullable',
            'tanggal_lahir'         => 'nullable|date',
            'pendidikan_terakhir'   => 'nullable',
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
            'foto'                  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'rfid_code' => 'nullable|string|max:50|unique:anggota,rfid_code',
            'ukuran_kaos'           => 'nullable|in:' . implode(',', SeragamHelper::UKURAN),
            'ukuran_kemeja'         => 'nullable|in:' . implode(',', SeragamHelper::UKURAN),
            'jumlah_kaos'           => 'nullable|integer|min:0|max:' . SeragamHelper::JUMLAH_MAKS,
            'jumlah_kemeja'         => 'nullable|integer|min:0|max:' . SeragamHelper::JUMLAH_MAKS,
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $this->uploadFoto( $request->file('foto'), $validated['nik_karyawan']);
        }

        if (!empty($validated['rfid_code'])) {
            $validated['rfid_code'] = RfidHelper::normalize(
                $validated['rfid_code']
            );
        } else {
            $validated['rfid_code'] = null;
        }

        $validated['tanggal_masuk_anggota'] = now();
        $validated['is_active']             = 1;
        $validated['password']              = $this->generatePassword(
            $validated['nama_lengkap'],
            $validated['nik_karyawan']
        );

        Anggota::create($this->normalisasiJumlahSeragam($validated));

        return redirect()
            ->route('anggota.index')
            ->with('success', 'Data anggota berhasil ditambahkan');
    }

    /*
    |--------------------------------------------------------------------------
    | FORM EDIT
    |--------------------------------------------------------------------------
    */
    public function edit(Anggota $anggota)
    {
        $provinsiList = Province::orderBy('name')->get(['code', 'name']);

        return view('anggota.edit', compact('provinsiList', 'anggota'));
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE DATA
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, Anggota $anggota)
    {
        
        $validated = $request->validate([
            'nama_lengkap'          => 'required|max:255',
            'nik_karyawan'          => 'required|digits:8|unique:anggota,nik_karyawan,' . $anggota->id,
            'departemen'            => 'required',
            'jabatan'               => 'required',
            'jenis_kelamin'         => 'required|in:Pria,Wanita',
            'status_karyawan'       => 'required',
            'tanggal_mulai_bekerja' => 'nullable|date',
            'jenis_identitas'       => 'nullable',
            'nomor_identitas'       => 'nullable|regex:/^\d+$/',
            'nomor_whatsapp'        => [
                                        'required',
                                        'regex:/^\+?[0-9]{10,13}$/',
                                        'unique:anggota,nomor_whatsapp,' . $anggota->id
                                        ],
            'email'                 => [
                                        'required',
                                        'email',
                                        'max:255',
                                        'unique:anggota,email,' . $anggota->id
                                        ],
            'tempat_lahir'          => 'nullable',
            'tanggal_lahir'         => 'nullable|date',
            'pendidikan_terakhir'   => 'nullable',
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
            'foto'                  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'rfid_code' => 'nullable|string|max:50|unique:anggota,rfid_code,' . $anggota->id,
            'ukuran_kaos'           => 'nullable|in:' . implode(',', SeragamHelper::UKURAN),
            'ukuran_kemeja'         => 'nullable|in:' . implode(',', SeragamHelper::UKURAN),
            'jumlah_kaos'           => 'nullable|integer|min:0|max:' . SeragamHelper::JUMLAH_MAKS,
            'jumlah_kemeja'         => 'nullable|integer|min:0|max:' . SeragamHelper::JUMLAH_MAKS,
        ]);

        if ($request->hasFile('foto')) {

            if (
                $anggota->foto &&
                $anggota->nik_karyawan !== $validated['nik_karyawan'] &&
                Storage::disk('public')->exists($anggota->foto)
            ) {
                Storage::disk('public')->delete($anggota->foto);
            }

            $validated['foto'] = $this->uploadFoto(
                $request->file('foto'),
                $validated['nik_karyawan']
            );
        }

        if (
            !$request->hasFile('foto') &&
            $anggota->foto &&
            $anggota->nik_karyawan !== $validated['nik_karyawan']
        ) {
            $oldPath = storage_path('app/public/' . $anggota->foto);

            if (File::exists($oldPath)) {

                $newRelativePath = 'anggota/' . $validated['nik_karyawan'] . '.webp';
                $newPath = storage_path('app/public/' . $newRelativePath);

                File::move($oldPath, $newPath);

                $validated['foto'] = $newRelativePath;
            }
        }

        if (!empty($validated['rfid_code'])) {
            $validated['rfid_code'] = RfidHelper::normalize(
                $validated['rfid_code']
            );
        } else {
            $validated['rfid_code'] = null;
        }
        
        $anggota->update($this->normalisasiJumlahSeragam($validated));

        return redirect()
            ->route('anggota.index')
            ->with('success', 'Data anggota berhasil diupdate');
    }

    /*
    |--------------------------------------------------------------------------
    | HAPUS DATA
    |--------------------------------------------------------------------------
    */
    public function destroy(Anggota $anggota)
    {
        if ($anggota->foto && Storage::disk('public')->exists($anggota->foto)) {
            Storage::disk('public')->delete($anggota->foto);
        }

        $anggota->delete();

        return back()->with('success', 'Data anggota berhasil dihapus');
    }

    /*
    |--------------------------------------------------------------------------
    | UPLOAD FOTO (private helper)
    |--------------------------------------------------------------------------
    */

    private function uploadFoto($file, string $nik): string
    {
        $folder = storage_path('app/public/anggota');

        if (! File::exists($folder)) {
            File::makeDirectory($folder, 0755, true);
        }

        $filename = $nik . '.webp';
        $path     = $folder . '/' . $filename;

        // Jika file dengan nama yang sama sudah ada, hapus dulu
        if (File::exists($path)) {
            File::delete($path);
        }

        Image::read($file)
            ->scaleDown(width: 800)
            ->toWebp(80)
            ->save($path);

        return 'anggota/' . $filename;
    }


    private function downloadAndStorePhoto(string $url, string $nik): string
    {
        $folder = storage_path('app/public/anggota');

        if (!File::exists($folder)) {
            File::makeDirectory($folder, 0755, true);
        }

        /*
        |--------------------------------------------------------------------------
        | GOOGLE DRIVE
        |--------------------------------------------------------------------------
        */
        if (str_contains($url, 'drive.google.com')) {

            if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $match)) {

                $fileId = $match[1];

            } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $url, $match)) {

                $fileId = $match[1];

            }

            if (!empty($fileId)) {
                $url = "https://drive.google.com/uc?export=download&id={$fileId}";
            }
        }

        $response = Http::timeout(60)
            ->withoutVerifying()
            ->get($url);

        if (!$response->successful()) {
            throw new \Exception('URL foto tidak dapat diakses');
        }

        $contentType = $response->header('Content-Type');

        if (!$contentType || !str_starts_with($contentType, 'image/')) {
            throw new \Exception('URL bukan file gambar');
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'foto_');

        file_put_contents(
            $tempFile,
            $response->body()
        );

        $filename = $nik . '.webp';
        $path = $folder . '/' . $filename;

        if (File::exists($path)) {
            File::delete($path);
        }

        try {

            Image::read($tempFile)
                ->scaleDown(width: 800)
                ->toWebp(80)
                ->save($path);

        } finally {

            if (file_exists($tempFile)) {
                unlink($tempFile);
            }

        }

        return 'anggota/' . $filename;
    }
    /*
    |--------------------------------------------------------------------------
    | FORM IMPORT
    |--------------------------------------------------------------------------
    */
    public function importForm()
    {
        $lastImport = TotalKaryawan::latest('created_at')->first();

        $bulanOptions = [];
        $tahunOptions = [];

        if ($lastImport) {

            // periode terakhir
            $current = \Carbon\Carbon::create(
                $lastImport->tahun,
                $lastImport->bulan,
                1
            );

            // periode berikutnya
            $next = $current->copy()->addMonth();

            $bulanOptions = [
                [
                    'bulan' => $current->month,
                    'tahun' => $current->year,
                ],
                [
                    'bulan' => $next->month,
                    'tahun' => $next->year,
                ],
            ];

            $tahunOptions = collect($bulanOptions)
                ->pluck('tahun')
                ->unique()
                ->values()
                ->toArray();
        }

        return view(
            'anggota.import',
            compact(
                'lastImport',
                'bulanOptions',
                'tahunOptions'
            )
        );
    }
    /*
    |--------------------------------------------------------------------------
    | PREVIEW IMPORT
    |--------------------------------------------------------------------------
    */
    public function importPreview(Request $request)
    {
        $request->validate([
            'file'         => 'required|mimes:xlsx,xls,csv',
            'bulan_import' => 'required|integer|min:1|max:12',
            'tahun_import' => 'required|integer|min:2020|max:2100',
        ]);

        $fileName = strtolower(
            pathinfo(
                $request->file('file')->getClientOriginalName(),
                PATHINFO_FILENAME
            )
        );

        $bulanKeywords = [
            1  => ['januari','january','jan'],
            2  => ['februari','february','feb'],
            3  => ['maret','march','mar'],
            4  => ['april','apr'],
            5  => ['mei','may'],
            6  => ['juni','june','jun'],
            7  => ['juli','july','jul'],
            8  => ['agustus','august','agu','agt'],
            9  => ['september','sep'],
            10 => ['oktober','october','oct','okt'],
            11 => ['november','nov'],
            12 => ['desember','december','dec','des']
        ];

        $bulan = (int) $request->bulan_import;
        $tahun = (string) $request->tahun_import;

        $monthMatched = collect($bulanKeywords[$bulan])
            ->contains(fn($keyword) => str_contains($fileName, $keyword));

        $yearMatched = str_contains($fileName, $tahun);

        if (!$monthMatched || !$yearMatched) {

            return back()
                ->withErrors([
                    'file' => 'Nama file harus mengandung periode ' .
                        Carbon::create()->month($bulan)->translatedFormat('F') .
                        ' ' . $tahun . '.'
                ])
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDASI PERIODE IMPORT
        |--------------------------------------------------------------------------
        */
        $lastImport = TotalKaryawan::latest('created_at')->first();

        if ($lastImport) {

            $lastPeriod = Carbon::create(
                $lastImport->tahun,
                $lastImport->bulan,
                1
            );

            $selectedPeriod = Carbon::create(
                (int) $request->tahun_import,
                (int) $request->bulan_import,
                1
            );

            $diffMonths =
                (($selectedPeriod->year - $lastPeriod->year) * 12)
                + ($selectedPeriod->month - $lastPeriod->month);

            // Mundur ke periode lama
            if ($diffMonths < 0) {
                return back()->withErrors([
                    'periode' =>
                        'Periode import tidak boleh lebih kecil dari periode terakhir (' .
                        $lastPeriod->translatedFormat('F Y') . ').'
                ])->withInput();
            }

            // Melompat lebih dari 1 bulan
            if ($diffMonths > 1) {

                $nextPeriod = $lastPeriod->copy()->addMonth();

                return back()->withErrors([
                    'periode' =>
                        'Periode harus berurutan. Setelah ' .
                        $lastPeriod->translatedFormat('F Y') .
                        ' hanya boleh mengimport ' .
                        $nextPeriod->translatedFormat('F Y') . '.'
                ])->withInput();
            }

        }

        $path = $request->file('file')->store('temp-import');

        try {

            $rows = Excel::toCollection(
                null,
                storage_path('app/private/' . $path)
            )->first();

        } catch (\Throwable $e) {

            Storage::delete($path);

            return back()
                ->withErrors([
                    'file' => 'File Excel tidak dapat dibaca. Pastikan file tidak rusak dan tidak di proteksi.'
                ])
                ->withInput();
        }

        if (!$rows || $rows->count() <= 1) {
            Storage::delete($path);
            return back()->withErrors(['file' => 'File kosong']);
        }

        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */
        $header = $rows->first()
            ->map(fn ($v) => trim(strtolower((string) $v)))
            ->toArray();

        $allowedHeaders = [
            'nik_karyawan', 'nama_lengkap', 'departemen', 'jabatan',
            'jenis_kelamin', 'status_karyawan', 'tanggal_mulai_bekerja',
            'jenis_identitas', 'nomor_identitas', 'email', 'nomor_whatsapp',
            'tempat_lahir', 'tanggal_lahir', 'pendidikan_terakhir',
            'provinsi_domisili', 'kota_domisili', 'kecamatan_domisili',
            'kelurahan_domisili', 'alamat_domisili',
            'provinsi_ktp', 'kota_ktp', 'kecamatan_ktp',
            'kelurahan_ktp', 'alamat_ktp', 'foto',
            'ukuran_kaos', 'ukuran_kemeja',
            'jumlah_kaos', 'jumlah_kemeja',
            'sudah_punya_kaos', 'sudah_punya_kemeja', // alias lama → dibaca sebagai jumlah
        ];

        $requiredHeaders = [
            'nik_karyawan', 'nama_lengkap', 'departemen',
            'jabatan', 'jenis_kelamin', 'status_karyawan',
        ];

        $unknownHeaders = array_diff($header, $allowedHeaders);
        if (!empty($unknownHeaders)) {
            Storage::delete($path);
            return back()->withErrors([
                'file' => 'Terdapat header tidak dikenal: ' . implode(', ', $unknownHeaders),
            ]);
        }

        $missingHeaders = array_diff($requiredHeaders, $header);
        if (!empty($missingHeaders)) {
            Storage::delete($path);
            return back()->withErrors([
                'file' => 'Header wajib tidak ditemukan: ' . implode(', ', $missingHeaders),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | MAPPING BARIS  —  simpan nomor baris Excel asli (baris 1 = header)
        |
        | Filter HANYA membuang:
        |   - baris yang SEMUA kolomnya kosong (baris benar-benar kosong)
        |   - baris yang NIK-nya adalah kata TOTAL / GRAND TOTAL / JUMLAH (footer)
        |
        | Baris dengan NIK kosong tapi kolom lain ada isi → TETAP MASUK,
        | akan ditangkap sebagai error "NIK Karyawan kosong" di tahap validasi.
        |--------------------------------------------------------------------------
        */
        $rawDataRows = $rows->count() - 1;

        $cleanRows = $rows
            ->skip(1)
            ->map(fn ($row, $excelIndex) => collect($header)
                ->mapWithKeys(fn ($col, $i) => [$col => $row[$i] ?? null])
                ->put('_excel_row', $excelIndex + 2)   // +2: baris 1 adalah header
            )
            ->filter(function ($row) {
                $nik = trim((string) ($row['nik_karyawan'] ?? ''));

                // Buang footer TOTAL/GRAND TOTAL/JUMLAH
                if (in_array(strtoupper($nik), ['TOTAL', 'GRAND TOTAL', 'JUMLAH'])) return false;
                if (str_starts_with(strtoupper($nik), 'TOTAL'))  return false;
                if (str_starts_with(strtoupper($nik), 'GRAND'))  return false;
                if (str_starts_with(strtoupper($nik), 'JUMLAH')) return false;

                // Buang baris yang benar-benar kosong semua kolom (tanpa _excel_row)
                $dataValues = $row->except('_excel_row')
                    ->filter(fn ($v) => trim((string) $v) !== '');

                return $dataValues->isNotEmpty();
            })
            ->values();

        $totalRows   = $cleanRows->count();
        $removedRows = $rawDataRows - $totalRows;

        if ($totalRows === 0) {
            Storage::delete($path);
            return back()->withErrors([
                'file' => 'Tidak ditemukan data yang valid. Import dibatalkan.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDASI PREVIEW
        |--------------------------------------------------------------------------
        */
        $previewErrors = [];
        $seenNik       = [];

        foreach ($cleanRows as $row) {
            $excelRow     = $row['_excel_row'];
            $nik          = trim((string) ($row['nik_karyawan'] ?? ''));
            $namaLengkap  = trim((string) ($row['nama_lengkap'] ?? ''));
            $departemen   = trim((string) ($row['departemen'] ?? ''));
            $jenisKelamin = trim((string) ($row['jenis_kelamin'] ?? ''));
            $email        = trim((string) ($row['email'] ?? ''));
            $wa           = preg_replace('/(?!^\+)[^0-9]/', '', trim((string) ($row['nomor_whatsapp'] ?? '')));

            if ($nik === '')
                $previewErrors[] = "Baris {$excelRow}: NIK Karyawan kosong.";

            if ($namaLengkap === '')
                $previewErrors[] = "Baris {$excelRow}: Nama Lengkap kosong.";

            if ($departemen === '')
                $previewErrors[] = "Baris {$excelRow}: Departemen kosong.";

            if ($jenisKelamin === '')
                $previewErrors[] = "Baris {$excelRow}: Jenis Kelamin kosong.";

            if ($nik !== '') {
                if (isset($seenNik[$nik])) {
                    $previewErrors[] = "Baris {$excelRow}: NIK {$nik} duplikat dengan baris {$seenNik[$nik]}.";
                } else {
                    $seenNik[$nik] = $excelRow;
                }
            }

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
                $previewErrors[] = "Baris {$excelRow}: Format email tidak valid ({$email}).";

            if ($wa !== '' && !preg_match('/^\+?[0-9]{10,13}$/', $wa))
                $previewErrors[] = "Baris {$excelRow}: Nomor WhatsApp tidak valid ({$wa}).";

            // Data seragam (opsional — kosong boleh, isi harus dikenali)
            foreach (['ukuran_kaos' => 'Ukuran Kaos', 'ukuran_kemeja' => 'Ukuran Kemeja'] as $kolom => $label) {
                if (!SeragamHelper::validUkuran($row[$kolom] ?? null)) {
                    $previewErrors[] = "Baris {$excelRow}: {$label} tidak valid (" . trim((string) $row[$kolom]) . "). Gunakan: " . implode(', ', SeragamHelper::UKURAN) . ".";
                }
            }

            foreach (['kaos' => 'Kaos', 'kemeja' => 'Kemeja'] as $jenis => $label) {
                $nilai = $this->nilaiJumlahSeragam($row, $jenis);

                if (!SeragamHelper::validJumlah($nilai)) {
                    $previewErrors[] = "Baris {$excelRow}: Jumlah {$label} tidak valid (" . trim((string) $nilai) . "). Isi angka 0 sampai " . SeragamHelper::JUMLAH_MAKS . ", atau Ya/Tidak.";
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | SIMPAN SESSION & KIRIM KE VIEW
        |
        | Kirim $cleanRows lengkap (bukan hanya 20) agar blade bisa filter
        | baris error dengan benar. Take(20) dilakukan di blade.
        |--------------------------------------------------------------------------
        */
        session([
            'anggota_import_file' => $path,
            'bulan_import'        => $request->bulan_import,
            'tahun_import'        => $request->tahun_import,
        ]);

        return view('anggota.import-preview', [
            'rows'          => $cleanRows,      // semua baris (blade yang take/filter)
            'totalRows'     => $totalRows,
            'removedRows'   => $removedRows,
            'previewErrors' => $previewErrors,
            'header'        => $header,
        ]);
    }
    /*
    |--------------------------------------------------------------------------
    | PROCESS IMPORT
    |--------------------------------------------------------------------------
    */
    public function importProcess()
    {
        $path        = session('anggota_import_file');
        $bulanImport = session('bulan_import');
        $tahunImport = session('tahun_import');
 
        if (!$bulanImport || !$tahunImport) {
            return redirect()
                ->route('anggota.import.form')
                ->withErrors(['file' => 'Periode import tidak ditemukan']);
        }
 
        $periodeMasuk  = \Carbon\Carbon::create($tahunImport, $bulanImport, 1)->startOfMonth();
        $periodeKeluar = \Carbon\Carbon::create($tahunImport, $bulanImport, 1)->endOfMonth();
 
        if (!$path) {
            return redirect()
                ->route('anggota.import.form')
                ->withErrors(['file' => 'Session import sudah habis']);
        }
 
        try {
 
            if (!Storage::exists($path)) {
                return redirect()
                    ->route('anggota.import.form')
                    ->withErrors(['file' => 'File import tidak ditemukan']);
            }
 
            $excelRows = Excel::toCollection(
                null,
                storage_path('app/private/' . $path)
            )->first();
 
            if (!$excelRows || $excelRows->count() <= 1) {
                return redirect()
                    ->route('anggota.import.form')
                    ->withErrors(['file' => 'File kosong']);
            }
 
            $header = $excelRows->first()
                ->map(fn ($v) => trim(strtolower($v)))
                ->toArray();
 
            /*
            |--------------------------------------------------------------------------
            | FILTER — sama persis dengan importPreview()
            | Buang: baris benar-benar kosong & footer TOTAL/GRAND/JUMLAH
            | Baris NIK kosong tapi ada data lain → tetap masuk (safety-net di bawah)
            |--------------------------------------------------------------------------
            */
            $rows = $excelRows
                ->skip(1)
                ->map(fn ($row) => collect($header)
                    ->mapWithKeys(fn ($col, $i) => [$col => $row[$i] ?? null])
                )
                ->filter(function ($row) {
                    $nik = trim((string) ($row['nik_karyawan'] ?? ''));
 
                    // Buang footer
                    if (in_array(strtoupper($nik), ['TOTAL', 'GRAND TOTAL', 'JUMLAH'])) return false;
                    if (str_starts_with(strtoupper($nik), 'TOTAL'))  return false;
                    if (str_starts_with(strtoupper($nik), 'GRAND'))  return false;
                    if (str_starts_with(strtoupper($nik), 'JUMLAH')) return false;
 
                    // Buang baris yang benar-benar kosong semua kolom
                    return $row->filter(fn ($v) => trim((string) $v) !== '')->isNotEmpty();
                })
                ->values();
 
            /*
            |--------------------------------------------------------------------------
            | VALIDASI JUMLAH NIK
            |--------------------------------------------------------------------------
            */
            $importNik = $rows->pluck('nik_karyawan')
                ->filter()
                ->map(fn ($v) => trim((string) $v))
                ->values();
 
            $validNikCount = $importNik->count();
 
            if ($validNikCount === 0) {
                return redirect()
                    ->route('anggota.import.form')
                    ->withErrors(['file' => 'Import dibatalkan. Tidak ditemukan NIK karyawan yang valid pada file Excel.']);
            }
 
            $totalAktif = Anggota::where('is_active', 1)->count();
 
            if ($totalAktif > 0 && $validNikCount < ($totalAktif * 0.5)) {
                return redirect()
                    ->route('anggota.import.form')
                    ->withErrors([
                        'file' =>
                            "Import dibatalkan. Data pada file hanya {$validNikCount} anggota, " .
                            "sedangkan anggota aktif saat ini {$totalAktif}. " .
                            "Kemungkinan file Excel tidak lengkap atau kolom NIK bermasalah.",
                    ]);
            }
 
            $newMembers      = [];
            $updatedMembers  = [];
            $uploadedPhotos  = [];
            $existingAnggota = Anggota::withTrashed()->get()->keyBy('nik_karyawan');

            // Pengambilan seragam di sekretariat yang masih berlaku, per anggota & jenis.
            // Nilai Excel = jumlah AWAL; jumlah akhir = Excel + pengambilan tercatat,
            // jadi mengimpor ulang file yang sama tidak menghapus pengambilan yang sudah terjadi.
            $pengambilanAktif = PengambilanSeragam::aktif()
                ->selectRaw('anggota_id, jenis, COUNT(*) as total')
                ->groupBy('anggota_id', 'jenis')
                ->get()
                ->mapWithKeys(fn ($r) => [$r->anggota_id . '|' . $r->jenis => (int) $r->total]);
 
            DB::beginTransaction();
 
            foreach ($rows as $index => $row) {
 
                $nik          = trim((string) ($row['nik_karyawan'] ?? ''));
                $namaLengkap  = $this->cleanValue($row['nama_lengkap'] ?? null);
                $departemen   = $this->cleanValue($row['departemen'] ?? null);
                $jenisKelamin = $this->normalizeJenisKelamin(
                    $row['jenis_kelamin'] ?? null
                );
 
                /*
                |--------------------------------------------------------------
                | SAFETY-NET validasi field wajib
                |--------------------------------------------------------------
                */
                if (!$nik)
                    throw new \Exception('Baris ' . ($index + 2) . ': NIK Karyawan masih kosong.');
 
                if (!$namaLengkap)
                    throw new \Exception('Baris ' . ($index + 2) . ': Nama Lengkap masih kosong.');
 
                if (!$departemen)
                    throw new \Exception('Baris ' . ($index + 2) . ': Departemen masih kosong.');
 
                if (!$jenisKelamin)
                    throw new \Exception('Baris ' . ($index + 2) . ': Jenis Kelamin masih kosong.');
 
                $email = trim((string) ($row['email'] ?? ''));
 
                $nomorWhatsapp = preg_replace(
                    '/(?!^\+)[^0-9]/',
                    '',
                    trim((string) ($row['nomor_whatsapp'] ?? ''))
                );
 
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
                    throw new \Exception('Baris ' . ($index + 2) . ': Format email tidak valid');
 
                if ($nomorWhatsapp !== '' && !preg_match('/^\+?[0-9]{10,13}$/', $nomorWhatsapp))
                    throw new \Exception('Baris ' . ($index + 2) . ': Nomor WhatsApp harus 10-13 digit');

                if (!SeragamHelper::validUkuran($row['ukuran_kaos'] ?? null))
                    throw new \Exception('Baris ' . ($index + 2) . ': Ukuran Kaos tidak valid');

                if (!SeragamHelper::validUkuran($row['ukuran_kemeja'] ?? null))
                    throw new \Exception('Baris ' . ($index + 2) . ': Ukuran Kemeja tidak valid');

                if (!SeragamHelper::validJumlah($this->nilaiJumlahSeragam($row, 'kaos')))
                    throw new \Exception('Baris ' . ($index + 2) . ': Jumlah Kaos tidak valid');

                if (!SeragamHelper::validJumlah($this->nilaiJumlahSeragam($row, 'kemeja')))
                    throw new \Exception('Baris ' . ($index + 2) . ': Jumlah Kemeja tidak valid');
 
                /*
                |--------------------------------------------------------------
                | TANGGAL MULAI BEKERJA
                | Prioritas:
                |   1. Nilai dari Excel (jika ada)
                |   2. Nilai di DB yang sudah ada (jika existing & tidak kosong)
                |   3. Derive dari NIK: YYMMXXXX → akhir bulan YYYY-MM
                |--------------------------------------------------------------
                */
                $tanggalMulaiBekerja = $this->parseExcelDate($row['tanggal_mulai_bekerja'] ?? null);
 
                if ($tanggalMulaiBekerja === null) {
                    $existingCheck = $existingAnggota->get($nik);
                    if (!$existingCheck || empty($existingCheck->tanggal_mulai_bekerja)) {
                        $tanggalMulaiBekerja = $this->nikToTanggalMulaiBekerja($nik);
                    }
                }
 
                $jabatan = $this->cleanValue($row['jabatan'] ?? null);
                $domisili = $this->findWilayahIds(
                    $row['provinsi_domisili'] ?? null,
                    $row['kota_domisili'] ?? null,
                    $row['kecamatan_domisili'] ?? null,
                    $row['kelurahan_domisili'] ?? null
                );

                $ktp = $this->findWilayahIds(
                    $row['provinsi_ktp'] ?? null,
                    $row['kota_ktp'] ?? null,
                    $row['kecamatan_ktp'] ?? null,
                    $row['kelurahan_ktp'] ?? null
                );
 
                $data = [
                    'nama_lengkap'          => $namaLengkap,
                    'nik_karyawan'          => $nik,
                    'jenis_kelamin'         => $jenisKelamin,
                    'departemen'            => $departemen,
                    'jabatan'               => $jabatan,
                    'status_karyawan'       => $this->normalizeStatusKaryawan($this->cleanValue($row['status_karyawan'] ?? null)) ?? 'Aktif',
                    'tanggal_mulai_bekerja' => $tanggalMulaiBekerja,
                    'jenis_identitas'       => $this->cleanValue($row['jenis_identitas'] ?? null),
                    'nomor_identitas'       => $this->cleanValue($row['nomor_identitas'] ?? null),
                    'email'                 => $this->cleanValue($email ?: null),
                    'nomor_whatsapp'        => $nomorWhatsapp ?: null,
                    'tempat_lahir'          => $this->cleanValue($row['tempat_lahir'] ?? null),
                    'tanggal_lahir'         => $this->parseExcelDate($row['tanggal_lahir'] ?? null),
                    'pendidikan_terakhir'   => $this->cleanValue($row['pendidikan_terakhir'] ?? null),
                    'provinsi_domisili'     => $domisili['provinsi_id'],
                    'kota_domisili'         => $domisili['kota_id'],
                    'kecamatan_domisili'    => $domisili['kecamatan_id'],
                    'kelurahan_domisili'    => $domisili['kelurahan_id'],
                    'alamat_domisili'       => $this->cleanValue($row['alamat_domisili'] ?? null),
                    'provinsi_ktp'          => $ktp['provinsi_id'],
                    'kota_ktp'              => $ktp['kota_id'],
                    'kecamatan_ktp'         => $ktp['kecamatan_id'],
                    'kelurahan_ktp'         => $ktp['kelurahan_id'],
                    'alamat_ktp'            => $this->cleanValue($row['alamat_ktp'] ?? null),
                    'ukuran_kaos'           => SeragamHelper::normalizeUkuran($row['ukuran_kaos'] ?? null),
                    'ukuran_kemeja'         => SeragamHelper::normalizeUkuran($row['ukuran_kemeja'] ?? null),
                    'jumlah_kaos'           => SeragamHelper::parseJumlah($this->nilaiJumlahSeragam($row, 'kaos')),
                    'jumlah_kemeja'         => SeragamHelper::parseJumlah($this->nilaiJumlahSeragam($row, 'kemeja')),
                    'foto'                  => null,
                ];
 
                /*
                |--------------------------------------------------------------
                | FOTO
                |--------------------------------------------------------------
                */
                $fotoUrl = trim((string) ($row['foto'] ?? ''));
 
                if (!empty($fotoUrl)) {
                    if (preg_match('#/storage/(.+)$#', $fotoUrl, $match)) {
                        $data['foto'] = $match[1];
                    } else {
                        $data['foto'] = $this->downloadAndStorePhoto($fotoUrl, $nik);
                        $uploadedPhotos[] = $data['foto'];
                    }
                }
 
                /*
                |--------------------------------------------------------------
                | UPSERT
                |--------------------------------------------------------------
                */
                $existing = $existingAnggota->get($nik);
 
                if ($existing) {
 
                    unset($data['nik_karyawan']);

                     if ($existing->trashed()) {
                        $existing->restore();
                    }
 
                    if ($data['jabatan'] === null) unset($data['jabatan']);
 
                    // Jangan overwrite tanggal_mulai_bekerja yang sudah ada di DB
                    // jika Excel kosong (sudah ditangani di atas, tapi double-check)
                    if ($data['tanggal_mulai_bekerja'] === null) {
                        unset($data['tanggal_mulai_bekerja']);
                    }
 
                    $data = collect($data)
                        ->filter(fn ($value) => $value !== null)
                        ->toArray();  

                    foreach (['kaos', 'kemeja'] as $jenis) {
                        if (isset($data["jumlah_{$jenis}"])) {
                            $data["jumlah_{$jenis}"] += $pengambilanAktif->get($existing->id . '|' . $jenis, 0);
                        }
                    }
 
                    if ($existing->tanggal_keluar_anggota) {
                        $data['tanggal_keluar_anggota'] = null;
                        $data['tanggal_masuk_anggota']  = $periodeMasuk;
                        $data['is_active']              = 1;
                    }
 
                    $existing->update($data);
                    $updatedMembers[] = $nik;
 
                } else {
 
                    if ($data['jabatan'] === null) $data['jabatan'] = 'Jabatan Kosong';

                    $data['tanggal_masuk_anggota']  = $periodeMasuk;
                    $data['tanggal_keluar_anggota'] = null;
                    $data['is_active']              = 1;

                    // Anggota baru: sel jumlah dikosongkan → 0 (kolom NOT NULL, default 0).
                    Anggota::create($this->normalisasiJumlahSeragam($data));
                    $newMembers[] = ['nik' => $nik, 'nama' => $namaLengkap];
                }
            }
 
            /*
            |--------------------------------------------------------------------------
            | TANDAI RESIGN — anggota tidak ada di file import
            |--------------------------------------------------------------------------
            */
            $resignedMembers = Anggota::whereNotIn('nik_karyawan', $importNik)
                ->whereNull('tanggal_keluar_anggota')
                ->get();
 
            Anggota::whereIn('id', $resignedMembers->pluck('id'))
                ->update([
                    'tanggal_keluar_anggota' => $periodeKeluar,
                    'is_active'              => 0,
                ]);
 
            DB::commit();
            \App\Jobs\BackfillPasswordJob::dispatch();

            TotalKaryawan::updateOrCreate(
                [
                    'bulan' => (int) $bulanImport,
                    'tahun' => (int) $tahunImport,
                ],
                [
                    'total_karyawan' => 0,
                ]
            );
 
            $failedRows = [];
 
            return view('anggota.import-result', compact(
                'newMembers',
                'updatedMembers',
                'resignedMembers',
                'failedRows'
            ));
 
        } catch (\Throwable $e) {
 
            DB::rollBack();
 
            foreach ($uploadedPhotos ?? [] as $photo) {
                if ($photo && Storage::disk('public')->exists($photo)) {
                    Storage::disk('public')->delete($photo);
                }
            }
 
            $message = $e->getMessage();
 
            if (str_contains($message, 'nama_lengkap'))
                $message = 'Terdapat anggota yang Nama Lengkapnya kosong. Silakan periksa file Excel.';
            elseif (str_contains($message, 'nik_karyawan'))
                $message = 'Terdapat anggota yang NIK Karyawannya kosong. Silakan periksa file Excel.';
            elseif (str_contains($message, 'Duplicate entry'))
                $message = 'Terdapat NIK yang duplikat pada file Excel.';
            elseif (str_contains($message, 'Integrity constraint'))
                $message = 'Terdapat data wajib yang belum diisi pada file Excel.';
            elseif (str_contains($message, 'Column'))
                $message = 'Terdapat kolom wajib yang kosong atau format data tidak sesuai. Silakan periksa file Excel.';
 
            return redirect()
                ->route('anggota.import.form')
                ->withErrors(['file' => '⚠ ' . $message]);
 
        } finally {
 
            if ($path && Storage::exists($path)) {
                Storage::delete($path);
            }
 
            session()->forget([
                'anggota_import_file',
                'bulan_import',
                'tahun_import',
            ]);
        }
    }

    private function findWilayahIds(
        ?string $provinsi,
        ?string $kota,
        ?string $kecamatan,
        ?string $kelurahan
    ): array {

        $provinsiId  = null;
        $kotaId      = null;
        $kecamatanId = null;
        $kelurahanId = null;

        // PROVINSI
        if (!empty($provinsi)) {

            $province = DB::table('indonesia_provinces')
                ->whereRaw('UPPER(name) = ?', [
                    strtoupper(trim($provinsi))
                ])
                ->first();

            $provinsiId = $province?->code;
        }

        // KOTA
        if ($provinsiId && !empty($kota)) {

            $city = DB::table('indonesia_cities')
                ->where('province_code', $provinsiId)
                ->whereRaw('UPPER(name) = ?', [
                    strtoupper(trim($kota))
                ])
                ->first();

            $kotaId = $city?->code;
        }

        // KECAMATAN
        if ($kotaId && !empty($kecamatan)) {

            $district = DB::table('indonesia_districts')
                ->where('city_code', $kotaId)
                ->whereRaw('UPPER(name) = ?', [
                    strtoupper(trim($kecamatan))
                ])
                ->first();

            $kecamatanId = $district?->code;
        }

        // KELURAHAN
        if ($kecamatanId && !empty($kelurahan)) {

            $village = DB::table('indonesia_villages')
                ->where('district_code', $kecamatanId)
                ->whereRaw('UPPER(name) = ?', [
                    strtoupper(trim($kelurahan))
                ])
                ->first();

            $kelurahanId = $village?->code;
        }

        return [
            'provinsi_id'  => $provinsiId,
            'kota_id'      => $kotaId,
            'kecamatan_id' => $kecamatanId,
            'kelurahan_id' => $kelurahanId,
        ];
    }

    private function normalizeJenisKelamin($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        $upper = strtoupper($value);

        $mapping = [
            'FEMALE (WANITA)' => 'Wanita',
            'FEMALE'          => 'Wanita',
            'WANITA'          => 'Wanita',
            'Perempuan'       => 'Wanita',

            'MALE (PRIA)'     => 'Pria',
            'MALE'            => 'Pria',
            'PRIA'            => 'Pria',
            'LAKI-LAKI'       => 'Pria',
            'LAKI LAKI'       => 'Pria',
        ];

        return $mapping[$upper] ?? $value;
    }

    private function normalizeStatusKaryawan($value): ?string
    {
        if ($value === null) return null;

        $value = trim((string) $value);

        if ($value === '') return null;

        $mapping = [
            'PERMANENT'  => 'Permanen',
            'PERMANEN'   => 'Permanen',
            'TETAP'      => 'Permanen',
            'CONTRACT'   => 'Kontrak',
            'KONTRAK'    => 'Kontrak',
            'KONTRAK'    => 'Kontrak',
        ];

        return $mapping[strtoupper($value)] ?? $value;
    }
 
    /*
    |--------------------------------------------------------------------------
    | NIK → TANGGAL MULAI BEKERJA (private helper)
    |
    | Format NIK: YYMMXXXX
    |   YY ≤ 2-digit tahun sekarang  →  20YY
    |   YY >  2-digit tahun sekarang →  19YY
    |
    | Contoh (tahun sekarang 2026, cutoff = 26):
    |   93042001  →  1993-04-30
    |   00112003  →  2000-11-30
    |   21021002  →  2021-02-28
    |--------------------------------------------------------------------------
    */
    private function nikToTanggalMulaiBekerja(string $nik): ?string
    {
        if (strlen($nik) < 4) return null;
 
        $yy = (int) substr($nik, 0, 2);
        $mm = (int) substr($nik, 2, 2);
 
        if ($mm < 1 || $mm > 12) return null;
 
        $currentYY = (int) date('y');
        $yyyy      = $yy <= $currentYY ? 2000 + $yy : 1900 + $yy;
 
        try {
            return \Carbon\Carbon::create($yyyy, $mm, 1)
                ->endOfMonth()
                ->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PARSE EXCEL DATE (private helper)
    |--------------------------------------------------------------------------
    */
    private function parseExcelDate($value): ?string
    {
        if (!$value) return null;

        try {
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date
                    ::excelToDateTimeObject($value)
                    ->format('Y-m-d');
            }
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * jumlah_kaos / jumlah_kemeja wajib angka (kolom NOT NULL, default 0).
     * Kosong / null dari form atau Excel dijadikan 0. Kunci yang memang tidak dikirim
     * dibiarkan, sehingga tidak menimpa nilai yang sudah tersimpan.
     */
    private function normalisasiJumlahSeragam(array $data): array
    {
        foreach (['jumlah_kaos', 'jumlah_kemeja'] as $kolom) {
            if (array_key_exists($kolom, $data)) {
                $data[$kolom] = (int) ($data[$kolom] ?? 0);
            }
        }

        return $data;
    }

    /**
     * Ambil isi sel jumlah kaos/kemeja dari satu baris Excel.
     * Header baru `jumlah_*` didahulukan; header lama `sudah_punya_*` tetap dibaca.
     * Mengembalikan sel pertama yang terisi (boolean FALSE dianggap terisi), atau null.
     */
    private function nilaiJumlahSeragam($row, string $jenis)
    {
        foreach (["jumlah_{$jenis}", "sudah_punya_{$jenis}"] as $kolom) {
            $v = $row[$kolom] ?? null;

            if (is_bool($v) || trim((string) $v) !== '') {
                return $v;
            }
        }

        return null;
    }

    private function cleanValue($value)
    {
        $value = trim((string)$value);

        if (
            $value === '' ||
            strtoupper($value) === 'NULL' ||
            strtoupper($value) === 'N/A' ||
            $value === '-'
        ) {
            return null;
        }

        return $value;
    }

    public function export()
    {
        $namaFile = 'DataAnggota_' . now()->translatedFormat('F_Y') . '.xlsx';

        return Excel::download(
            new AnggotaAktifExport(),
            $namaFile
        );
    }

     public function saveTotalKaryawan(Request $request)
    {
        $request->validate([
            'bulan'           => 'required|integer|min:1|max:12',
            'tahun'           => 'required|integer|min:2020|max:2100',
            'total_karyawan'  => 'required|integer|min:0',
        ]);
 
        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;
 
        /*
        |----------------------------------------------------------------------
        | CEK: apakah periode ini sudah pernah diimport?
        | Record di total_karyawan dibuat otomatis saat importProcess().
        | Jika belum ada → import belum dilakukan → tolak.
        |----------------------------------------------------------------------
        */
        $record = \App\Models\TotalKaryawan::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();
 
        if (!$record) {
            $namaBulan = \Carbon\Carbon::create($tahun, $bulan, 1)
                ->translatedFormat('F');
 
            return response()->json([
                'ok'      => false,
                'message' => "Anda belum import anggota pada bulan {$namaBulan} dan tahun {$tahun}.",
            ], 422);
        }
 
        $record->update(['total_karyawan' => $request->total_karyawan]);
 
        return response()->json([
            'ok'      => true,
            'message' => 'Total karyawan berhasil disimpan.',
        ]);
    }

    private function generatePassword(string $namaLengkap, string $nik): string
    {
        $plain = strtoupper($namaLengkap . '_' . $nik);

        return Hash::make($plain);
    }

    public function qrImage(Anggota $anggota)
    {
        $qrCode = new QrCode(
            data: $anggota->ulid,
            size: 300,
            margin: 10,
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return response($result->getString(), 200)
            ->header('Content-Type', $result->getMimeType());
    }
    

}