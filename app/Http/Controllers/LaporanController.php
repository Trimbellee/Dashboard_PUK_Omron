<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Models\Laporan;
use App\Models\Pengajuan;
use Illuminate\Http\Request;
use App\Models\User;


class LaporanController extends Controller
{

    public function manual($id)
    {
        $pengajuan = Pengajuan::findOrFail($id);

        $anggaran = $pengajuan->Biaya ?? $pengajuan->biaya ?? 0;

        // cek laporan sudah ada atau belum
        $laporan = Laporan::where('pengajuan_id', $id)->first();

        $items = [];

        if ($laporan) {
            $items = json_decode($laporan->anggaran_aktual_json, true);
        }

        return view('laporan.manual', compact(
            'pengajuan',
            'anggaran',
            'laporan',
            'items'
        ));
    }


public function storeManual(Request $request)
{
    $request->validate([
        'pengajuan_id' => 'required',
        'nama_kegiatan' => 'required',
        'tujuan' => 'required',
    ]);

    $pengajuan = Pengajuan::findOrFail($request->pengajuan_id);

    /*
    =================
    FORMAT DATA AKTUAL
    =================
    */

    $items = [];

    foreach ($request->aktual as $row) {

        $item = trim($row['item'] ?? '');

        $nominal = (int) preg_replace(
            '/[^0-9]/',
            '',
            $row['nominal'] ?? '0'
        );

        // skip hanya jika item & nominal sama-sama kosong
        if ($item === '' && $nominal <= 0) {
            continue;
        }

        $items[] = [
            'item' => $item,
            'nominal' => $nominal
        ];
    }

    $totalAktual = array_sum(array_column($items, 'nominal'));

    /*
    =================
    SIMPAN / UPDATE LAPORAN
    =================
    */

    Laporan::where('pengajuan_id', $pengajuan->id)->delete();

    $laporan = Laporan::create([
        'pengajuan_id' => $pengajuan->id,
        'nama_kegiatan' => $request->nama_kegiatan,
        'tujuan' => $request->tujuan,
        'tanggal' => $request->tanggal,
        'jam' => $request->jam,
        'tempat' => $request->tempat,
        'anggaran_aktual_json' => json_encode($items)
    ]);

    /*
    =================
    GENERATE PDF
    =================
    */

    $pdf = Pdf::loadView('laporan.pdf.laporan_kegiatan', [
        'laporan' => $laporan,
        'items' => $items,
        'pengajuan' => $pengajuan,
        'totalAktual' => $totalAktual
    ]);

    $path = "file/laporan/{$pengajuan->id}.pdf";

    /*
    =================
    HAPUS FILE LAMA
    =================
    */

    if (
        $pengajuan->lampiran_laporan &&
        Storage::exists($pengajuan->lampiran_laporan)
    ) {
        Storage::delete($pengajuan->lampiran_laporan);
    }

    /*
    =================
    SIMPAN FILE BARU
    =================
    */

    $pdfContent = $pdf->output();

    Storage::put($path, $pdfContent);

    $anggaran = $pengajuan->Biaya ?? $pengajuan->biaya ?? 0;

    /*
    =================
    UPDATE PENGAJUAN
    =================
    */

    $pengajuan->lampiran_laporan = $path;
    $pengajuan->aktual_biaya = $totalAktual;
    $pengajuan->dikembalikan = $anggaran - $totalAktual;

    $pengajuan->save();

    /*
    =================
    KIRIM EMAIL NOTIF
    =================
    */

    $emails = $this->emailsByRole([
        'Bendahara',
        'Ketua',
        'Sekretaris'
    ]);

    // attachment PDF
    $attachments = [];

    $attachments[] =
        \App\Services\BrevoMailService::makeAttachmentFromContent(
            $pdfContent,
            'Laporan-Kegiatan-' . $pengajuan->id . '.pdf'
        );

    if (!empty($emails)) {

        try {

            $laporanMail = new \App\Mail\LaporanUploadedMail($pengajuan);

            // build dulu supaya subject terisi
            $laporanMail->build();

            $html = $laporanMail->render();

            $subject = $laporanMail->subject;

            foreach ($emails as $email) {

                try {

                    \App\Services\BrevoMailService::send(
                        $email,
                        $email,
                        $subject,
                        $html,
                        $attachments
                    );

                    \Log::info('Email laporan berhasil dikirim', [
                        'email' => $email
                    ]);

                } catch (\Throwable $e) {

                    \Log::error('Email laporan gagal', [
                        'email' => $email,
                        'error' => $e->getMessage()
                    ]);
                }
            }

        } catch (\Throwable $e) {

            \Log::error('Render LaporanUploadedMail gagal', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /*
    =================
    NOTIF SELISIH DANA
    =================
    */

    $this->notifySelisihDana($pengajuan);

    return redirect()
        ->route('pengajuan.index')
        ->with('success', 'Laporan berhasil disimpan!');
}

protected function emailsByRole(array $roles)
{
    return User::whereIn('role', $roles)
        ->pluck('email')
        ->filter()
        ->unique()
        ->values()
        ->toArray();
}

protected function notifySelisihDana($p)
{
    $pembuat = User::where('username',$p->created_by)->first();

    if (!$pembuat?->email) {
        return;
    }

    if ($p->dikembalikan != 0) {

        foreach ([$pembuat->email] as $email) {

            \App\Services\BrevoMailService::sendMailable(
                $email,
                $email,
                new \App\Mail\SelisihDanaMail($p)
            );

        }
    }
}

}
