<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pengajuan;
use Carbon\Carbon;
use League\Csv\Reader;

class ImportPengajuanCsv extends Command
{
    protected $signature = 'import:pengajuan';
    protected $description = 'Import pengajuan dari CSV Google Sheet';

    public function handle()
    {
        $path = storage_path('app/import/pengajuan.csv');
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);

        foreach ($csv as $row) {

            Pengajuan::updateOrCreate(
                ['id' => trim($row['ID'])],
                [
                    'nama' => $row['Nama'],
                    'bidang' => $row['Bidang'],
                    'tujuan' => $row['Tujuan'],
                    'keperluan' => $row['Keperluan'],
                    'biaya' => (int)$row['Biaya'],

                    'lampiran_proposal' => $row['LampiranProposal'] ?: null,
                    'aktual_biaya' => $row['AktualBiaya'] !== '' ? (int)$row['AktualBiaya'] : null,
                    'dikembalikan' => $row['Dikembalikan'] !== '' ? (int)$row['Dikembalikan'] : null,
                    'lampiran_laporan' => $row['LampiranLaporan'] ?: null,

                    'status' => $row['Status'] ?: 'Menunggu',
                    'approver' => $row['Approver'] ?: null,
                    'catatan' => $row['Catatan'] ?: null,
                    'tanggal' => $row['Tanggal']
                        ? Carbon::createFromFormat('d/m/Y H:i:s', $row['Tanggal'])
                        : null,
                ]
            );
        }

        $this->info('✅ Import pengajuan selesai');
    }
}

