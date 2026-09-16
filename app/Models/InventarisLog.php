<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarisLog extends Model
{
    use HasFactory;

    protected $table = 'inventaris_log';

    protected $fillable = [
        'barang_id',
        'user_id',
        'aksi',
        'detail'
    ];

    protected $casts = [
        'detail' => 'array'
    ];

    // Atau gunakan guarded
    // protected $guarded = ['id'];

    public function barang()
    {
        return $this->belongsTo(InventarisBarang::class, 'barang_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}