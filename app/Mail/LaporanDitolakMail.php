<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LaporanDitolakMail extends Mailable
{
    use Queueable, SerializesModels;

    // $role = 'Sekretaris' atau 'Ketua', $catatan = alasan penolakan
    public function __construct(public $laporan, public string $role, public string $catatan) {}

    public function build()
    {
        return $this
            ->subject("Laporan Bendahara Ditolak oleh {$this->role}")
            ->view('emails.laporan-ditolak')
            ->with([
                'laporan' => $this->laporan,
                'role'    => $this->role,
                'catatan' => $this->catatan,
            ]);
    }
}