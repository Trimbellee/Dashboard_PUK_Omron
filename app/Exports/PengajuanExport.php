<?php

namespace App\Exports;

use App\Models\Pengajuan;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PengajuanExport implements FromCollection, WithHeadings, WithMapping
{
    protected $bulan;
    protected $tahun;
    protected $status;

    public function __construct($bulan, $tahun, $status)
    {
        $this->bulan  = $bulan;
        $this->tahun  = $tahun;
        $this->status = $status;
    }

    public function collection()
    {
        $q = Pengajuan::query();

        if ($this->bulan) {
            $q->whereMonth('tanggal', $this->bulan);
        }

        if ($this->tahun) {
            $q->whereYear('tanggal', $this->tahun);
        }

        if ($this->status && $this->status !== 'all') {
            $q->where('status', $this->status);
        }

        return $q->orderBy('tanggal', 'asc')->get();
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Nama',
            'Bidang',
            'Tujuan',
            'Keperluan',
            'Biaya Diajukan',
            'Realisasi',
            'Sisa Dana',
            'Status',
        ];
    }

    public function map($p): array
    {
        $sisa = $p->biaya - ($p->aktual_biaya ?? 0);

        return [
            \Carbon\Carbon::parse($p->tanggal)->format('d-m-Y'),
            $p->nama,
            $p->bidang,
            $p->tujuan,
            $p->keperluan,
            $p->biaya, 
            $p->sisa, 
            $p->aktual_biaya,
            $p->status,
        ];
    }
}
