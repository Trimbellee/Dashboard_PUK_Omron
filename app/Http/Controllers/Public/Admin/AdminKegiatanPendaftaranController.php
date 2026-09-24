<?php

namespace App\Http\Controllers\Public\Admin;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Kegiatan;
use App\Models\KegiatanPendaftaran;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminKegiatanPendaftaranController extends Controller
{
    private const LIVE_LIMIT    = 30;   // baris "sudah hadir" terbaru yang dikirim ke tiap device
    private const SEARCH_LIMIT  = 50;
    private const VERSION_TTL   = 60;   // detik — token versi kedaluwarsa sendiri → self-healing kalau ada perubahan dari luar
    private const PAYLOAD_TTL   = 300;

    /**
     * Status pendaftaran yang TIDAK boleh check-in.
     * Sesuaikan dengan status yang benar-benar ada di sistemmu (kalau tidak ada, aman diabaikan).
     */
    private const STATUS_BLOKIR = ['ditolak', 'dibatalkan'];

    // ══════════════════════════════════════════════════════════
    // Daftar pendaftar
    // ══════════════════════════════════════════════════════════

    public function index(Request $request, Kegiatan $kegiatan)
    {
        $kegiatan->load('customFields');

        $pendaftar = $kegiatan->pendaftar()
            ->with('anggota')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('cari'), function ($q) use ($request) {
                $like = '%' . $this->escapeLike(trim((string) $request->cari)) . '%';
                $q->whereHas('anggota', function ($sub) use ($like) {
                    $sub->where('nama_lengkap', 'like', $like)
                        ->orWhere('nik_karyawan', 'like', $like);
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('landing-page.admin.kegiatan.pendaftar', compact('kegiatan', 'pendaftar'));
    }

    public function tandaiHadir(Kegiatan $kegiatan, KegiatanPendaftaran $pendaftaran): RedirectResponse
    {
        abort_unless($pendaftaran->kegiatan_id === $kegiatan->id, 404);

        $jadiHadir = $pendaftaran->status !== 'hadir';

        $pendaftaran->forceFill([
            'status'   => $jadiHadir ? 'hadir' : 'diterima',
            'hadir_at' => $jadiHadir ? now() : null,
        ])->save();

        $this->bump($kegiatan);

        return back()->with('success', 'Status kehadiran diperbarui.');
    }

    public function destroy(Kegiatan $kegiatan, KegiatanPendaftaran $pendaftaran): RedirectResponse
    {
        abort_unless($pendaftaran->kegiatan_id === $kegiatan->id, 404);

        $this->authorizePendaftarAccess($kegiatan);

        $pendaftaran->delete();

        $this->bump($kegiatan);

        return back()->with('success', 'Pendaftar berhasil dihapus.');
    }

    public function exportCsv(Kegiatan $kegiatan): StreamedResponse
    {
        $kegiatan->load('customFields');
        $customFields = $kegiatan->customFields;

        $filename = str($kegiatan->judul)->slug() . '-pendaftar.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($kegiatan, $customFields) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM supaya Excel membaca UTF-8 dengan benar

            $header = ['No', 'Nama', 'NIK', 'Departemen', 'Status', 'Waktu Hadir', 'Tanggal Daftar'];
            foreach ($customFields as $field) {
                $header[] = $field->label;
            }
            fputcsv($out, $header);

            $no = 0;

            // chunkById (bukan chunk) → aman dari baris yang terlewat, dan penomoran tidak reset tiap 200 baris.
            $kegiatan->pendaftar()->with('anggota')->chunkById(200, function ($rows) use ($out, $customFields, &$no) {
                foreach ($rows as $row) {
                    $line = [
                        ++$no,
                        $row->anggota->nama_lengkap ?? '-',
                        $row->anggota->nik_karyawan ?? '-',
                        $row->anggota->departemen ?? '-',
                        $row->status,
                        $row->hadir_at ? Carbon::parse($row->hadir_at)->format('d-m-Y H:i') : '',
                        $row->created_at?->format('d-m-Y H:i') ?? '',
                    ];

                    foreach ($customFields as $field) {
                        $jawaban = $row->custom_values[$field->id] ?? '';
                        $line[]  = is_array($jawaban) ? implode(', ', $jawaban) : $jawaban;
                    }

                    fputcsv($out, $line);
                }
            });

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function cariAnggota(Request $request, Kegiatan $kegiatan): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $like           = '%' . $this->escapeLike($q) . '%';
        $sudahDaftarIds = $kegiatan->pendaftar()->pluck('anggota_id');

        $anggota = Anggota::query()
            ->where('is_active', true)
            ->whereNotIn('id', $sudahDaftarIds)
            ->where(function ($query) use ($like) {
                $query->where('nama_lengkap', 'like', $like)
                    ->orWhere('nik_karyawan', 'like', $like);
            })
            ->limit(10)
            ->get(['id', 'nama_lengkap as nama', 'nik_karyawan', 'departemen']);

        return response()->json($anggota);
    }

    public function storeManual(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        $this->authorizePendaftarAccess($kegiatan);
        $kegiatan->load('customFields');

        $rules = [
            'anggota_id' => ['required', 'integer', Rule::exists('anggota', 'id')],
        ];

        foreach ($kegiatan->customFields as $field) {
            $key         = "custom_fields.{$field->id}";
            $fieldRules  = [$field->wajib ? 'required' : 'nullable'];
            $fieldRules[] = match ($field->tipe) {
                'number'   => 'numeric',
                'checkbox' => 'boolean',
                'select'   => Rule::in($field->opsi ?? []),
                default    => 'string',
            };
            $rules[$key] = $fieldRules;
        }

        $validated = $request->validate($rules);

        $sudahDaftar = KegiatanPendaftaran::where('kegiatan_id', $kegiatan->id)
            ->where('anggota_id', $validated['anggota_id'])
            ->exists();

        if ($sudahDaftar) {
            return back()->with('error', 'Anggota ini sudah terdaftar di kegiatan ini.');
        }

        if ($kegiatan->kuota !== null && $kegiatan->kuota_terisi >= $kegiatan->kuota) {
            return back()->with('error', 'Kuota kegiatan ini sudah penuh.');
        }

        KegiatanPendaftaran::create([
            'kegiatan_id'   => $kegiatan->id,
            'anggota_id'    => $validated['anggota_id'],
            'status'        => 'diterima',
            'custom_values' => $validated['custom_fields'] ?? [],
        ]);

        $this->bump($kegiatan);

        return back()->with('success', 'Pendaftar berhasil ditambahkan.');
    }

    private function authorizePendaftarAccess(Kegiatan $kegiatan): void
    {
        $jabatan = session('anggota.jabatan_puk', '');

        abort_unless(
            strcasecmp((string) $jabatan, (string) $kegiatan->created_by) === 0
                || strcasecmp((string) $jabatan, 'sekretaris') === 0,
            403
        );
    }

    // ══════════════════════════════════════════════════════════
    // Scan kehadiran
    // ══════════════════════════════════════════════════════════

    public function scanPage(Kegiatan $kegiatan): View
    {
        return view('landing-page.admin.kegiatan.scan', [
            'kegiatan' => $kegiatan,
            // Data awal ikut di HTML → halaman langsung terisi tanpa request pertama.
            'initial'  => $this->livePayload($kegiatan),
        ]);
    }

    /**
     * Check-in satu kode (kartu RFID / NIK / ULID dari QR).
     *
     * Aman untuk banyak device: penandaan hadir memakai UPDATE atomik
     * ("hanya jika belum hadir"), jadi dua device yang men-scan kartu yang sama
     * di detik yang sama tidak akan sama-sama dapat "berhasil".
     */
    public function scan(Request $request, Kegiatan $kegiatan): JsonResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:100'],
        ]);

        $kodeRaw = trim($validated['kode']);
        // Buang separator dan uppercase-kan supaya UID NFC ("04:a3:b2:c1")
        // dan reader USB ("04A3B2C1") tetap cocok.
        $kode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $kodeRaw));

        $anggota = $this->cariAnggotaByKode($kodeRaw, $kode);

        if (!$anggota) {
            return response()->json([
                'status'  => 'tidak_dikenali',
                'message' => 'Kartu/kode tidak dikenali.',
            ], 404);
        }

        $info = [
            'nama'       => $anggota->nama_lengkap,
            'nik'        => $anggota->nik_karyawan,
            'departemen' => $anggota->departemen ?? '-',
        ];

        $pendaftaran = KegiatanPendaftaran::where('kegiatan_id', $kegiatan->id)
            ->where('anggota_id', $anggota->id)
            ->first(['id', 'status', 'hadir_at']);

        if (!$pendaftaran) {
            return response()->json([
                'status'  => 'belum_daftar',
                'message' => "{$anggota->nama_lengkap} belum terdaftar di kegiatan ini.",
                'anggota' => $info,
            ], 404);
        }

        if (in_array($pendaftaran->status, self::STATUS_BLOKIR, true)) {
            return response()->json([
                'status'  => 'tidak_boleh',
                'message' => "{$anggota->nama_lengkap} tidak dapat check-in (status pendaftaran: {$pendaftaran->status}).",
                'anggota' => $info,
            ], 403);
        }

        if ($pendaftaran->status === 'hadir') {
            return $this->responSudahHadir($info, $pendaftaran->hadir_at);
        }

        // ── UPDATE atomik: hanya berhasil kalau statusnya belum 'hadir' ──
        $diubah = KegiatanPendaftaran::where('id', $pendaftaran->id)
            ->where('status', '!=', 'hadir')
            ->update(['status' => 'hadir', 'hadir_at' => now()]);

        if ($diubah === 0) {
            // Kalah cepat dari device lain.
            $hadirAt = KegiatanPendaftaran::whereKey($pendaftaran->id)->value('hadir_at');

            return $this->responSudahHadir($info, $hadirAt);
        }

        $this->bump($kegiatan);

        return response()->json([
            'status'  => 'berhasil',
            'message' => "Selamat datang, {$anggota->nama_lengkap}!",
            'anggota' => $info,
            'row_id'  => $pendaftaran->id,
            // Payload terbaru (di-cache per versi → device lain memakainya juga saat polling).
            'live'    => $this->livePayload($kegiatan),
        ]);
    }

    /**
     * Live list "sudah hadir".
     *  - ?v=TOKEN → tidak ada perubahan? balas kecil tanpa list (nol query berat).
     *  - ?q=TEXT  → cari di seluruh yang sudah hadir (min. 2 karakter).
     */
    public function daftarHadirJson(Request $request, Kegiatan $kegiatan): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) >= 2) {
            $kegiatan->loadMissing('customFields');
            $like = '%' . $this->escapeLike($q) . '%';

            $list = $this->hadirQuery($kegiatan)
                ->whereHas('anggota', function ($s) use ($like) {
                    $s->where(fn ($w) => $w->where('nama_lengkap', 'like', $like)
                                           ->orWhere('nik_karyawan', 'like', $like));
                })
                ->limit(self::SEARCH_LIMIT)
                ->get()
                ->map(fn ($row) => $this->serializePendaftaran($row, $kegiatan))
                ->values();

            return response()->json(['list' => $list]);
        }

        $ver = $this->version($kegiatan);

        if ($request->query('v') === $ver) {
            return response()->json(['changed' => false, 'version' => $ver]);
        }

        return response()->json($this->livePayload($kegiatan));
    }

    // ══════════════════════════════════════════════════════════
    // Helpers
    // ══════════════════════════════════════════════════════════

    /**
     * Payload live (statistik + 30 hadir terbaru). Di-cache per VERSI, jadi 10 device
     * yang polling setelah satu perubahan hanya memicu satu kali query ke database.
     */
    private function livePayload(Kegiatan $kegiatan): array
    {
        $ver = $this->version($kegiatan);

        return Cache::remember("kegiatan:{$kegiatan->id}:scan:{$ver}", self::PAYLOAD_TTL, function () use ($kegiatan, $ver) {
            $kegiatan->loadMissing('customFields');

            $s = $kegiatan->pendaftar()->toBase()
                ->selectRaw("COUNT(*) AS terdaftar, COALESCE(SUM(status = 'hadir'), 0) AS hadir")
                ->first();

            $list = $this->hadirQuery($kegiatan)
                ->limit(self::LIVE_LIMIT)
                ->get()
                ->map(fn ($row) => $this->serializePendaftaran($row, $kegiatan))
                ->values()
                ->all();

            return [
                'changed' => true,
                'version' => $ver,
                'stats'   => [
                    'terdaftar' => (int) ($s->terdaftar ?? 0),
                    'hadir'     => (int) ($s->hadir ?? 0),
                ],
                'fields'  => $kegiatan->customFields->pluck('label')->values()->all(),
                'list'    => $list,
            ];
        });
    }

    private function hadirQuery(Kegiatan $kegiatan)
    {
        return $kegiatan->pendaftar()
            ->with('anggota:id,nama_lengkap,nik_karyawan,departemen')
            ->where('status', 'hadir')
            ->orderByDesc('hadir_at')
            ->orderByDesc('id');
    }

    /** Token versi per kegiatan. Kedaluwarsa tiap 60 dtk → perubahan dari luar pun ikut tersinkron. */
    private function version(Kegiatan $kegiatan): string
    {
        return Cache::remember($this->versionKey($kegiatan), self::VERSION_TTL, fn () => Str::random(10));
    }

    /** Panggil setiap kali data pendaftaran/kehadiran berubah. */
    private function bump(Kegiatan $kegiatan): void
    {
        Cache::put($this->versionKey($kegiatan), Str::random(10), self::VERSION_TTL);
    }

    private function versionKey(Kegiatan $kegiatan): string
    {
        return "kegiatan:{$kegiatan->id}:scan:ver";
    }

    /**
     * Cari anggota dari kode. Semua kolom yang dicek ber-index unik → cepat.
     * Fallback lambat (REPLACE) hanya jalan kalau tidak ketemu, untuk data lama
     * yang rfid_code-nya masih tersimpan dengan separator.
     */
    private function cariAnggotaByKode(string $raw, string $kode): ?Anggota
    {
        $cols = ['id', 'nama_lengkap', 'nik_karyawan', 'departemen'];

        $anggota = Anggota::query()
            ->where(function ($q) use ($raw, $kode) {
                $q->where('nik_karyawan', $raw)->orWhere('ulid', $raw);

                if ($kode !== '') {
                    $q->orWhere('rfid_code', $kode);
                }
            })
            ->first($cols);

        if ($anggota || $kode === '') {
            return $anggota;
        }

        return Anggota::query()
            ->whereRaw("UPPER(REPLACE(REPLACE(REPLACE(rfid_code, ':', ''), '-', ''), ' ', '')) = ?", [$kode])
            ->first($cols);
    }

    private function responSudahHadir(array $info, $hadirAt): JsonResponse
    {
        $jam = $hadirAt ? Carbon::parse($hadirAt)->format('H:i') : null;

        return response()->json([
            'status'  => 'sudah_hadir',
            'message' => "{$info['nama']} sudah check-in sebelumnya" . ($jam ? " (jam {$jam})." : '.'),
            'anggota' => $info,
        ]);
    }

    /** Satu baris "sudah hadir" — dipakai live list dan pencarian. */
    private function serializePendaftaran(KegiatanPendaftaran $row, Kegiatan $kegiatan): array
    {
        $custom = $kegiatan->customFields->map(function ($field) use ($row) {
            $jawaban = $row->custom_values[$field->id] ?? null;

            return [
                'label' => $field->label,
                'value' => $field->tipe === 'checkbox'
                    ? ($jawaban ? 'Ya' : 'Tidak')
                    : (is_array($jawaban) ? implode(', ', $jawaban) : ($jawaban ?: '-')),
            ];
        })->values();

        $waktu = $row->hadir_at ? Carbon::parse($row->hadir_at) : $row->updated_at;

        return [
            'id'         => $row->id,
            'nama'       => $row->anggota->nama_lengkap ?? '-',
            'nik'        => $row->anggota->nik_karyawan ?? '-',
            'departemen' => $row->anggota->departemen ?? '-',
            'custom'     => $custom,
            'tgl_hadir'  => $waktu?->translatedFormat('d M Y, H:i') ?? '-',
        ];
    }

    private function escapeLike(string $s): string
    {
        return addcslashes($s, '%_\\');
    }
}