<?php

namespace App\Exports;

use App\Models\Kegiatan;
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

class PendaftarKegiatanExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithEvents
{
    /** Kolom tetap sebelum custom field: No, Nama, NIK, Departemen, Status, Waktu Hadir, Tanggal Daftar */
    private const KOLOM_TETAP = 7;

    protected Kegiatan $kegiatan;
    protected $customFields;
    protected int $rowCount = 0;
    protected int $no = 0;

    public function __construct(Kegiatan $kegiatan)
    {
        $this->kegiatan     = $kegiatan;
        $this->customFields = $kegiatan->customFields;
    }

    public function collection()
    {
        $data = $this->kegiatan->pendaftar()
            ->with('anggota')
            ->orderBy('id')
            ->get();

        $this->rowCount = $data->count();

        return $data;
    }

    public function headings(): array
    {
        $head = ['No', 'Nama', 'NIK', 'Departemen', 'Status', 'Waktu Hadir', 'Tanggal Daftar'];

        foreach ($this->customFields as $field) {
            $head[] = $field->label;
        }

        return $head;
    }

    public function map($row): array
    {
        $line = [
            ++$this->no,
            $row->anggota->nama_lengkap ?? '-',
            (string) ($row->anggota->nik_karyawan ?? '-'),   // string supaya angka 0 di depan NIK tidak hilang
            $row->anggota->departemen ?? '-',
            ucfirst(str_replace('_', ' ', $row->status)),
            $row->hadir_at ? \Carbon\Carbon::parse($row->hadir_at)->format('d-m-Y H:i') : '-',
            $row->created_at?->format('d-m-Y H:i') ?? '-',
        ];

        foreach ($this->customFields as $field) {
            $jawaban = $row->custom_values[$field->id] ?? null;

            $line[] = $field->tipe === 'checkbox'
                ? ($jawaban ? 'Ya' : 'Tidak')
                : (is_array($jawaban) ? implode(', ', $jawaban) : ($jawaban ?: '-'));
        }

        return $line;
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 6,
            'B' => 28,
            'C' => 16,
            'D' => 24,
            'E' => 14,
            'F' => 20,
            'G' => 20,
        ];

        foreach ($this->customFields as $i => $field) {
            $widths[Coordinate::stringFromColumnIndex(self::KOLOM_TETAP + $i + 1)] = 22;
        }

        return $widths;
    }

    private function kolomTerakhir(): string
    {
        return Coordinate::stringFromColumnIndex(self::KOLOM_TETAP + $this->customFields->count());
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

        // No, NIK, Status, Waktu Hadir, Tgl Daftar rata tengah
        foreach (['A', 'C', 'E', 'F', 'G'] as $col) {
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

                // Warna teks kolom status (kolom E)
                for ($r = 2; $r <= $lastRow; $r++) {
                    $val = $sheet->getCell("E{$r}")->getValue();
                    $color = match ($val) {
                        'Hadir'         => '00A650',
                        'Diterima'      => '1F4E78',
                        'Ditolak kuota' => 'C00000',
                        default         => '000000',
                    };
                    $sheet->getStyle("E{$r}")->getFont()->setBold(true)->getColor()->setRGB($color);
                }
            },
        ];
    }
}