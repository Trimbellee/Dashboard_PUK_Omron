<?php

namespace App\Exports;

use App\Models\Anggota;
use App\Helpers\SeragamHelper;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Village;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;

use Maatwebsite\Excel\Events\AfterSheet;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class AnggotaAktifExport extends StringValueBinder implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithStyles,
    WithEvents,
    WithCustomValueBinder
{
    protected $provinces;
    protected $cities;
    protected $districts;
    protected $villages;

    public function __construct()
    {
        $this->provinces = Province::pluck('name', 'code');
        $this->cities    = City::pluck('name', 'code');
        $this->districts = District::pluck('name', 'code');
        $this->villages  = Village::pluck('name', 'code');
    }

    public function collection(): Collection
    {
        return Anggota::query()
            ->where('is_active', 1)
            ->orderBy('nama_lengkap')
            ->orderBy('nik_karyawan')
            ->get();
    }

    public function headings(): array
    {
        return [
            'nik_karyawan',
            'nama_lengkap',
            'jenis_kelamin',
            'departemen',
            'jabatan',
            'status_karyawan',
            'tanggal_mulai_bekerja',
            'jenis_identitas',
            'nomor_identitas',
            'nomor_whatsapp',
            'email',
            'tempat_lahir',
            'tanggal_lahir',
            'pendidikan_terakhir',

            'provinsi_domisili',
            'kota_domisili',
            'kecamatan_domisili',
            'kelurahan_domisili',
            'alamat_domisili',

            'provinsi_ktp',
            'kota_ktp',
            'kecamatan_ktp',
            'kelurahan_ktp',
            'alamat_ktp',

            'foto',

            'ukuran_kaos',
            'ukuran_kemeja',
            'sudah_punya_kaos',
            'sudah_punya_kemeja',
        ];
    }

    public function map($row): array
    {
        return [

            (string) $row->nik_karyawan,

            $row->nama_lengkap,

            $row->jenis_kelamin,

            $row->departemen,

            $row->jabatan,

            $row->status_karyawan,

            $row->tanggal_mulai_bekerja
                ? Carbon::parse($row->tanggal_mulai_bekerja)
                    ->format('Y-m-d')
                : null,

            $row->jenis_identitas,

            (string) $row->nomor_identitas,

            (string) $row->nomor_whatsapp,

            $row->email,

            $row->tempat_lahir,

            $row->tanggal_lahir
                ? Carbon::parse($row->tanggal_lahir)
                    ->format('Y-m-d')
                : null,

            $row->pendidikan_terakhir,

            $this->provinces[$row->provinsi_domisili]
                ?? $row->provinsi_domisili,

            $this->cities[$row->kota_domisili]
                ?? $row->kota_domisili,

            $this->districts[$row->kecamatan_domisili]
                ?? $row->kecamatan_domisili,

            $this->villages[$row->kelurahan_domisili]
                ?? $row->kelurahan_domisili,

            $row->alamat_domisili,

            $this->provinces[$row->provinsi_ktp]
                ?? $row->provinsi_ktp,

            $this->cities[$row->kota_ktp]
                ?? $row->kota_ktp,

            $this->districts[$row->kecamatan_ktp]
                ?? $row->kecamatan_ktp,

            $this->villages[$row->kelurahan_ktp]
                ?? $row->kelurahan_ktp,

            $row->alamat_ktp,

            $row->foto
            ? asset('storage/' . $row->foto)
            : null,

            $row->ukuran_kaos,

            $row->ukuran_kemeja,

            SeragamHelper::labelYaTidak($row->sudah_punya_kaos),

            SeragamHelper::labelYaTidak($row->sudah_punya_kemeja),
        ];
    }

    /**
     * Paksa NIK, KTP, WA menjadi TEXT
     */
    public function bindValue(Cell $cell, $value): bool
    {
        $column = $cell->getColumn();

        if (in_array($column, ['A', 'I', 'J'])) {

            $cell->setValueExplicit(
                (string) $value,
                DataType::TYPE_STRING
            );

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                // Freeze header
                $sheet->freezePane('A2');

                // Auto filter
                $sheet->setAutoFilter(
                    $sheet->calculateWorksheetDimension()
                );

                // Kolom FOTO = Y
                $highestRow = $sheet->getHighestRow();

                for ($row = 2; $row <= $highestRow; $row++) {

                    $url = $sheet->getCell('Y' . $row)->getValue();

                    if (!empty($url)) {

                        $sheet->getCell('Y' . $row)
                            ->getHyperlink()
                            ->setUrl($url);

                        // Optional: tampilkan style hyperlink biru
                        $sheet->getStyle('Y' . $row)
                            ->applyFromArray([
                                'font' => [
                                    'underline' => true,
                                    'color' => [
                                        'rgb' => '0000FF'
                                    ],
                                ],
                            ]);
                    }
                }
            },

        ];
    }
}