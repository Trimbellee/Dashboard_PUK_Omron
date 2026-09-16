<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LaporanUploadedMail extends Mailable
{
     use Queueable, SerializesModels;

    public $p;
    public $statusDana;
    public $selisih;

    public function __construct($p)
    {
        $this->p = $p;

        if ($p->dikembalikan > 0) {
            $this->statusDana = 'sisa';
            $this->selisih = $p->dikembalikan;
        } elseif ($p->dikembalikan < 0) {
            $this->statusDana = 'kurang';
            $this->selisih = abs($p->dikembalikan);
        } else {
            $this->statusDana = 'pas';
            $this->selisih = 0;
        }
    }

    public function build()
    {
        return $this
            ->subject('Laporan Kegiatan Diunggah')
            ->view('emails.laporan-uploaded');
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
