<?php

namespace App\Exports;

use App\Models\Pengajuan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PengajuanExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithColumnFormatting,
    WithEvents
{
    protected $bulan;
    protected $tahun;
    protected $status;
    protected int $rowCount = 0;

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

        $data = $q->orderBy('tanggal', 'asc')->get();
        $this->rowCount = $data->count();

        return $data;
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
            'Aktual Biaya',
            'Status',
            'Approver',
        ];
    }

    public function map($p): array
    {
        return [
            $p->tanggal ? Date::PHPToExcel($p->tanggal) : null,
            $p->nama,
            $p->bidang,
            $p->tujuan,
            $p->keperluan,
            (float) $p->biaya,
            $p->aktual_biaya !== null ? (float) $p->aktual_biaya : null,
            $p->status,
            $p->approver ?? '-',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 25,
            'C' => 20,
            'D' => 30,
            'E' => 45,
            'F' => 18,
            'G' => 18,
            'H' => 14,
            'I' => 20,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'F' => '"Rp" #,##0',
            'G' => '"Rp" #,##0',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = max($this->rowCount + 1, 2);

        // Style header
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'size'  => 12,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E78'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'FFFFFF'],
                ],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Style isi data
        $sheet->getStyle("A2:I{$lastRow}")->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'D9D9D9'],
                ],
            ],
        ]);

        // Tanggal & status rata tengah, angka rata kanan
        $sheet->getStyle("A2:A{$lastRow}")
              ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F2:G{$lastRow}")
              ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("H2:H{$lastRow}")
              ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = max($this->rowCount + 1, 2);

                // Filter di header
                $sheet->setAutoFilter("A1:I{$lastRow}");

                // Header tetap terlihat saat scroll
                $sheet->freezePane('A2');

                // Warna selang-seling baris
                for ($r = 3; $r <= $lastRow; $r += 2) {
                    $sheet->getStyle("A{$r}:I{$r}")->getFill()
                          ->setFillType(Fill::FILL_SOLID)
                          ->getStartColor()->setRGB('EAF1F8');
                }

                // Warna teks kolom status (sekarang kolom H)
                for ($r = 2; $r <= $lastRow; $r++) {
                    $val = $sheet->getCell("H{$r}")->getValue();
                    $color = match ($val) {
                        'Disetujui' => '00A650',
                        'Ditolak'   => 'C00000',
                        'Menunggu'  => 'BF8F00',
                        default     => '000000',
                    };
                    $sheet->getStyle("H{$r}")->getFont()
                          ->setBold(true)->getColor()->setRGB($color);
                }
            },
        ];
    }
}