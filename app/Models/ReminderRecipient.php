<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReminderRecipient extends Model
{
    protected $fillable = [
        'agenda_event_id',
        'role',
        'email',
    ];

    public function agendaEvent()
    {
        return $this->belongsTo(AgendaEvent::class);
    }
}