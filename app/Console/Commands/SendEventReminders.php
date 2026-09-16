<?php

namespace App\Console\Commands;

use App\Models\AgendaEvent;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendEventReminders extends Command
{
    protected $signature = 'reminders:send';
    protected $description = 'Send event reminders based on reminder_offset_days using Brevo';

    public function handle()
    {
        $now = Carbon::now()->startOfDay();
        
        // Ambil event yang perlu reminder (belum dikirim, belum done, dan bukan -1)
        $events = AgendaEvent::where('status', '!=', 'done')
            ->where('reminder_offset_days', '!=', -1)
            ->where('reminder_sent', false) // Tambahkan kondisi ini
            ->get();

        if ($events->isEmpty()) {
            $this->info('Tidak ada event yang perlu reminder.');
            return;
        }

        $sent = 0;
        $failed = 0;

        foreach ($events as $event) {
            $reminderDate = $event->start_date->copy()->subDays($event->reminder_offset_days);
            
            // Kirim reminder jika hari ini adalah hari reminder
            if ($reminderDate->isToday()) {
                $recipients = $event->reminderRecipients;
                
                if ($recipients->isEmpty()) {
                    $this->warn("Event '{$event->title}' tidak memiliki recipient.");
                    continue;
                }

                try {
                    // Generate HTML content
                    $html = view('emails.reminder', [
                        'event' => $event,
                        'recipients' => $recipients,
                    ])->render();

                    $subject = '📅 Reminder: ' . $event->title;

                    $allSuccess = true;
                    foreach ($recipients as $recipient) {
                        try {
                            \App\Services\BrevoMailService::send(
                                $recipient->email,
                                $recipient->email,
                                $subject,
                                $html,
                                []
                            );

                            $this->info("✅ Reminder sent to {$recipient->email} for event: {$event->title}");
                            $sent++;

                            Log::info('Reminder email sent via command', [
                                'event_id' => $event->id,
                                'email' => $recipient->email,
                                'role' => $recipient->role,
                            ]);

                        } catch (\Throwable $e) {
                            $this->error("❌ Failed to send reminder to {$recipient->email}: " . $e->getMessage());
                            $failed++;
                            $allSuccess = false;
                            Log::error('Failed to send reminder email via command', [
                                'event_id' => $event->id,
                                'email' => $recipient->email,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    // Tandai sebagai sudah dikirim jika semua berhasil
                    if ($allSuccess) {
                        $event->update(['reminder_sent' => true]);
                    }

                } catch (\Throwable $e) {
                    $this->error("❌ Failed to render reminder email for event: {$event->title}");
                    Log::error('Failed to render reminder email in command', [
                        'event_id' => $event->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->info("✅ Reminder sending completed. Sent: {$sent}, Failed: {$failed}");
    }
}