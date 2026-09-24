<?php

namespace App\Exports;

use App\Http\Controllers\Public\Admin\SesiAbsensiController;
use App\Models\SesiAbsensi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SesiAbsensiExport implements FromCollection, WithHeadings, WithMapping
{
    private array $kolomLabels;

    public function __construct(private SesiAbsensi $sesi)
    {
        $this->kolomLabels = SesiAbsensiController::kolomOptions();
    }

    public function collection()
    {
        return $this->sesi->entri()->orderBy('created_at')->get();
    }

    public function headings(): array
    {
        $headings = ['No', 'Jam Hadir'];

        foreach ($this->sesi->kolom_ditampilkan as $kolom) {
            $headings[] = $this->kolomLabels[$kolom] ?? $kolom;
        }

        return $headings;
    }

    public function map($entri): array
    {
        static $no = 0;
        $no++;

        $row = [$no, $entri->created_at->format('d-m-Y H:i')];

        foreach ($this->sesi->kolom_ditampilkan as $kolom) {
            $row[] = $entri->data[$kolom] ?? '-';
        }

        return $row;
    }
}