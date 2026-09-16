<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LaporanSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public $laporan) {}

    public function build()
    {
        return $this
            ->subject('Laporan Bendahara Menunggu Persetujuan Sekretaris')
            ->view('emails.laporan-submitted')
            ->with(['laporan' => $this->laporan]);
    }
}