<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationProfile extends Model
{
    protected $table = 'organization_profile';

    protected $fillable = [
        'sejarah_singkat', 
        'visi', 
        'misi', 
        'periode_kepengurusan',
        'alamat_sekretariat', 
        'email_sekretariat', 
        'updated_by',
    ];

    protected $casts = [
        'misi' => 'array',
    ];
}