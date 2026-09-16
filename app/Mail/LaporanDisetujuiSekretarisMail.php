<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LaporanDisetujuiSekretarisMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public $laporan) {}

    public function build()
    {
        return $this
            ->subject('Laporan Bendahara Menunggu Persetujuan Ketua')
            ->view('emails.laporan-disetujui-sekretaris')
            ->with(['laporan' => $this->laporan]);
    }
}