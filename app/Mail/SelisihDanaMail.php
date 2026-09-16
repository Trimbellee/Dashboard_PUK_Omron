<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SelisihDanaMail extends Mailable
{
    use SerializesModels;

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
            ->subject('Informasi Selisih Dana Kegiatan')
            ->view('emails.selisih-dana');
    }
}