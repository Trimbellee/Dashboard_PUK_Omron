<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AnggotaOtpResetMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $namaLengkap,
        public string $kode,
    ) {}

    public function build()
    {
        return $this->subject('Kode OTP Reset Password - PUK OMRON')
            ->view('emails.anggota.otp-reset');
    }
}