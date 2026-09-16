<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $table = 'contacts';

    protected $fillable = ['anggota_id', 'nama', 'nik_karyawan', 'departemen', 'email', 'pesan', 'is_read'];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function anggota()
    {
        return $this->belongsTo(Anggota::class);
    }
}