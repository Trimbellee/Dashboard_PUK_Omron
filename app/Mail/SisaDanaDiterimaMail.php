<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SisaDanaDiterimaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public $p) {}

    public function build()
{
    return $this->subject('Sisa Dana Sudah Diterima Bendahara')
        ->view('emails.sisa_dana_diterima');
}

}
