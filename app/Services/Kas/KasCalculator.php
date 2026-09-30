<?php
namespace App\Services\Kas;

use App\Models\KasMaster;
use App\Models\BidangMaster;
use App\Models\LaporanParameter;

class KasCalculator
{
    private array $kas = [];        // KODE_KAS => baris konfigurasi kas
    private array $params = [];     // KODE_PARAM => nilai
    private array $bidangByKas = [];// KODE_KAS_TUJUAN => [KODE_BIDANG, ...]
    private array $in = [];
    private array $memo = [];
    private array $stack = [];

    public function __construct(array $kas, array $params, array $bidang = [])
    {
        foreach ($kas as $k) {
            $k['kode'] = self::norm($k['kode']);
            $this->kas[$k['kode']] = $k;
        }
        foreach ($params as $kode => $nilai) {
            $this->params[self::norm($kode)] = (float) $nilai;
        }
        foreach ($bidang as $b) {
            $kodeBidang = self::norm($b['kode']);
            $kodeKas    = self::norm($b['kas_tujuan'] ?? '');
            if ($kodeKas === '') continue;
            $this->bidangByKas[$kodeKas][] = $kodeBidang;
        }
    }

    /** Konfigurasi terkini dari database (kas & bidang aktif saja, urut). */
    public static function dariMaster(): self
    {
        $bidang = BidangMaster::where('aktif', true)->orderBy('urutan')->get()
            ->map(fn ($b) => ['kode' => $b->kode, 'nama' => $b->nama, 'kas_tujuan' => $b->kas_tujuan])
            ->all();

        return new self(
            KasMaster::where('aktif', true)->orderBy('urutan')->get()->toArray(),
            LaporanParameter::pluck('nilai', 'kode')->all(),
            $bidang
        );
    }

    /** Konfigurasi dari snapshot laporan. */
    public static function dariKonfigurasi(array $konf): self
    {
        return new self($konf['kas'] ?? [], $konf['parameter'] ?? [], $konf['bidang'] ?? []);
    }

    /** "Dana Sosial" -> DANA_SOSIAL, "Bidang 1" -> BIDANG_1 */
    public static function norm(string $s): string
    {
        return strtoupper(trim(preg_replace('/[^A-Za-z0-9]+/', '_', $s), '_'));
    }

    private static function normKeys(array $arr): array
    {
        $out = [];
        foreach ($arr as $k => $v) {
            $out[self::norm((string) $k)] = (int) $v;
        }
        return $out;
    }

    /**
     * Cek konfigurasi: variabel tak dikenal, siklus, pembagian nol.
     * Melempar FormulaException jika ada masalah.
     */
    public function validasi(): void
    {
        $this->hitung([]);
    }

    /**
     * @param array $in cos, saldo_awal[kode], debit_manual[kode], kredit_manual[kode],
     *                  bidang[NAMA_BIDANG] (realisasi mentah per nama bidang),
     *                  insentif, dana_lain, pajak[kode], sumbangan[kode]
     */
    public function hitung(array $in): array
    {
        $this->in = [
            'cos'           => (float) ($in['cos'] ?? 0),
            'saldo_awal'    => self::normKeys($in['saldo_awal'] ?? []),
            'debit_manual'  => self::normKeys($in['debit_manual'] ?? []),
            'kredit_manual' => self::normKeys($in['kredit_manual'] ?? []),
            'bidang'        => self::normKeys($in['bidang'] ?? []),
            'pajak'         => self::normKeys($in['pajak'] ?? []),
            'sumbangan'     => self::normKeys($in['sumbangan'] ?? []),
            'insentif'      => (int) ($in['insentif'] ?? 0),
            'dana_lain'     => (int) ($in['dana_lain'] ?? 0),
        ];
        $this->memo = [];
        $this->stack = [];

        $rows = [];
        $grand = ['saldo_awal' => 0, 'debit' => 0, 'kredit' => 0, 'total' => 0];

        foreach ($this->kas as $kode => $k) {
            $row = [
                'kode'       => strtolower($kode),
                'nama'       => $k['nama'],
                'saldo_awal' => $this->nilai('SALDO', $kode),
                'debit'      => $this->nilai('DEBIT', $kode),
                'kredit'     => $this->nilai('KREDIT', $kode),
                'total'      => $this->nilai('TOTAL', $kode),
            ];
            $rows[] = $row;

            $grand['saldo_awal'] += $row['saldo_awal'];
            $grand['debit']      += $row['debit'];
            $grand['kredit']     += $row['kredit'];
            $grand['total']      += $row['total'];
        }

        return ['rows' => $rows, 'grand' => $grand];
    }

    private function nilai(string $jenis, string $kode): int
    {
        $key = "{$jenis}:{$kode}";

        if (isset($this->memo[$key])) return $this->memo[$key];
        if (isset($this->stack[$key])) {
            throw new FormulaException("Rumus saling menunjuk (siklus) pada {$jenis}.{$kode}");
        }
        if (!isset($this->kas[$kode])) {
            throw new FormulaException("Kas '{$kode}' tidak dikenal atau tidak aktif");
        }

        $this->stack[$key] = true;
        $k = $this->kas[$kode];

        $v = match ($jenis) {
            'SALDO'  => ($k['saldo_awal_mode'] ?? 'carry_over') === 'carry_over'
                            ? ($this->in['saldo_awal'][$kode] ?? 0) : 0,
            'DEBIT'  => $this->hitungDebit($k, $kode),
            'KREDIT' => ($k['kredit_mode'] ?? 'manual') === 'rumus'
                            ? $this->rumus($k['kredit_rumus'] ?? '', "kredit {$kode}")
                            : ($this->in['kredit_manual'][$kode] ?? 0),
            'TOTAL'  => $this->nilai('SALDO', $kode) + $this->nilai('DEBIT', $kode) - $this->nilai('KREDIT', $kode),
        };

        unset($this->stack[$key]);
        return $this->memo[$key] = (int) $v;
    }

    private function hitungDebit(array $k, string $kode): int
    {
        $d = ($k['debit_mode'] ?? 'manual') === 'rumus'
            ? $this->rumus($k['debit_rumus'] ?? '', "debit {$kode}")
            : ($this->in['debit_manual'][$kode] ?? 0);

        // sumbangan otomatis menambah debit kas yang menerima sumbangan
        if (!empty($k['menerima_sumbangan'])) {
            $d += $this->in['sumbangan'][$kode] ?? 0;
        }
        return $d;
    }

    /** Jumlah realisasi semua bidang aktif yang kas_tujuan-nya = $kodeKas. */
    private function nilaiKreditBidang(string $kodeKas): int
    {
        $key = "KREDIT_BIDANG:{$kodeKas}";
        if (isset($this->memo[$key])) return $this->memo[$key];

        $total = 0;
        foreach ($this->bidangByKas[$kodeKas] ?? [] as $kodeBidang) {
            // $this->in['bidang'] diberi kunci NAMA bidang (dinormalisasi),
            // bukan kode; controller yang memetakan realisasi Pengajuan/Koperasi
            // ke sini berdasarkan nama bidang yang dipilih di form.
            $total += $this->in['bidang'][$kodeBidang] ?? 0;
        }

        return $this->memo[$key] = $total;
    }

    private function rumus(string $src, string $label): int
    {
        try {
            $hasil = Formula::parse($src)->evaluate(fn ($v) => $this->resolve($v));
        } catch (FormulaException $e) {
            throw new FormulaException("Rumus {$label}: " . $e->getMessage(), 0, $e);
        }
        return Formula::lantai($hasil);
    }

    private function resolve(string $name): float
    {
        if (!str_contains($name, '.')) {
            return match ($name) {
                'COS'       => $this->in['cos'],
                'INSENTIF'  => $this->in['insentif'],
                'DANA_LAIN' => $this->in['dana_lain'],
                default     => $this->params[$name]
                    ?? throw new FormulaException("Variabel '{$name}' tidak dikenal"),
            };
        }

        [$ns, $kode] = explode('.', $name, 2);

        return match ($ns) {
            'SALDO', 'DEBIT', 'KREDIT' => $this->nilai($ns, $kode),
            'PAJAK'         => $this->in['pajak'][$kode] ?? 0,
            'SUMBANGAN'     => $this->in['sumbangan'][$kode] ?? 0,
            'BIDANG'        => $this->in['bidang'][$kode] ?? 0,
            'KREDIT_BIDANG' => $this->nilaiKreditBidang($kode),
            default         => throw new FormulaException("Awalan '{$ns}' tidak dikenal"),
        };
    }
}