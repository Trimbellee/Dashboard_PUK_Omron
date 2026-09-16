<?php

namespace App\Mail;

use App\Models\Pengajuan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DanaDitransferMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public $p) {}

    public function build()
    {
        return $this->subject('Dana Pengajuan Sudah Ditransfer')
            ->view('emails.dana_ditransfer')
            ->with([
                'p' => $this->p
            ]);
    }
}