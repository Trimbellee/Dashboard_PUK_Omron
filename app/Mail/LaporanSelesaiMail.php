<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LaporanSelesaiMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public $laporan) {}

    public function build()
    {
        return $this
            ->subject('Laporan Bendahara Telah Disetujui Penuh')
            ->view('emails.laporan-selesai')
            ->with(['laporan' => $this->laporan]);
    }
}