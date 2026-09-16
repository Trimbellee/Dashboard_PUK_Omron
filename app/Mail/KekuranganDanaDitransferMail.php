<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class KekuranganDanaDitransferMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public $p) {}

    public function build()
{
    return $this->subject('Kekurangan Dana Sudah Ditransfer Bendahara')
        ->view('emails.kekurangan_dana_ditransfer');
}

}
