<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AgendaEvent extends Model
{
    const BIDANG_OPTIONS = [
        'Ketua',
        'Sekretaris',
        'Bendahara',
        'Bidang 1',
        'Bidang 2',
        'Bidang 3',
        'Bidang 4',
        'Bidang 5',
        'Bidang 6',
        'Korlap',
        'Garda Metal',
    ];

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'location', 
        'start_time',
        'end_time',
        'category',
        'bidang',
        'created_by',
        'reminder_offset_days',
        'reminder_sent',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'reminder_offset_days' => 'integer',
    ];

    // Relasi ke reminder recipients
    public function reminderRecipients()
    {
        return $this->hasMany(ReminderRecipient::class);
    }

    public function canBeManagedBy(?string $username, ?string $role): bool
    {
        return $this->canBeManagedByRole($role);
    }

    public function canBeManagedByRole(?string $role): bool
    {
        if ($role === 'Sekretaris') {
            return true;
        }

        return $this->created_by === $role;
    }
}