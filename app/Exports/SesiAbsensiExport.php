<?php

namespace App\Exports;

use App\Http\Controllers\Public\Admin\SesiAbsensiController;
use App\Models\SesiAbsensi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SesiAbsensiExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithEvents
{
    /** Kolom tetap di depan: No, Jam Hadir */
    private const KOLOM_AWAL = 2;

    private array $kolomLabels;
    private int $rowCount = 0;
    private int $no = 0;

    public function __construct(private SesiAbsensi $sesi)
    {
        $this->kolomLabels = SesiAbsensiController::kolomOptions();
    }

    public function collection()
    {
        $data = $this->sesi->entri()->orderBy('created_at')->get();

        $this->rowCount = $data->count();

        return $data;
    }

    public function headings(): array
    {
        $headings = ['No', 'Jam Hadir'];

        foreach ($this->sesi->kolom_ditampilkan as $kolom) {
            $headings[] = $this->kolomLabels[$kolom] ?? $kolom;
        }

        $headings[] = 'Lokasi';

        return $headings;
    }

    public function map($entri): array
    {
        $row = [++$this->no, $entri->created_at->format('d-m-Y H:i')];

        foreach ($this->sesi->kolom_ditampilkan as $kolom) {
            // string supaya angka 0 di depan (mis. NIK) tidak hilang di Excel
            $row[] = (string) ($entri->data[$kolom] ?? '-');
        }

        $row[] = $entri->data['_lokasi_nama'] ?? '-';

        return $row;
    }

    private function kolomTerakhir(): string
    {
        // kolom awal + kolom pilihan admin + kolom Lokasi
        return Coordinate::stringFromColumnIndex(
            self::KOLOM_AWAL + count($this->sesi->kolom_ditampilkan) + 1
        );
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 6, 'B' => 18];

        foreach ($this->sesi->kolom_ditampilkan as $i => $kolom) {
            $widths[Coordinate::stringFromColumnIndex(self::KOLOM_AWAL + $i + 1)] = 24;
        }

        $widths[$this->kolomTerakhir()] = 45;   // Lokasi

        return $widths;
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = $this->kolomTerakhir();
        $lastRow = max($this->rowCount + 1, 2);

        // Header
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Isi data
        $sheet->getStyle("A2:{$lastCol}{$lastRow}")->applyFromArray([
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9D9D9']]],
        ]);

        // No dan Jam Hadir rata tengah
        foreach (['A', 'B'] as $col) {
            $sheet->getStyle("{$col}2:{$col}{$lastRow}")
                  ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = $this->kolomTerakhir();
                $lastRow = max($this->rowCount + 1, 2);

                $sheet->setAutoFilter("A1:{$lastCol}{$lastRow}");
                $sheet->freezePane('A2');

                // Warna selang-seling baris
                for ($r = 3; $r <= $lastRow; $r += 2) {
                    $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()
                          ->setFillType(Fill::FILL_SOLID)
                          ->getStartColor()->setRGB('EAF1F8');
                }
            },
        ];
    }
}