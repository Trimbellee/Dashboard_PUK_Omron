<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PengajuanBaruMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public $p) {}

    public function build()
    {
        return $this
            ->subject('Pengajuan Dana Baru')
            ->view('emails.pengajuan-baru')
            ->with([
                'p' => $this->p
            ]);
    }
}
