<?php

namespace App\Http\Controllers;

use App\Models\AgendaEvent;
use App\Models\ReminderRecipient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AgendaController extends Controller
{
    public function index()
    {
        $users = User::whereNotNull('email')
        ->where('email', '!=', '')
        ->orderBy('role')
        ->get();

        return view('agenda.index', [
            'bidangOptions' => AgendaEvent::BIDANG_OPTIONS,
             'users' => $users, 
        ]);
    }

    public function data(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));
        $start = \Carbon\Carbon::parse($month . '-01')->startOfMonth()->subDays(7);
        $end = \Carbon\Carbon::parse($month . '-01')->endOfMonth()->addDays(7);

        $query = AgendaEvent::query()
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end]);
            });

        if ($request->filled('bidang')) {
            $query->where('bidang', $request->query('bidang'));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        $userRole = session('user')['role'] ?? null;
        $userBidang = session('user')['bidang'] ?? null;

        $events = $query->orderBy('start_date')->get()->map(function (AgendaEvent $e) use ($userRole, $userBidang) {
            return [
                'id' => $e->id,
                'title' => $e->title,
                'description' => $e->description,
                'start_date' => $e->start_date->format('Y-m-d'),
                'end_date' => $e->end_date->format('Y-m-d'),
                'location' => $e->location,
                'start_time' => $e->start_time ? $e->start_time->format('H:i') : null,
                'end_time' => $e->end_time ? $e->end_time->format('H:i') : null,
                'category' => $e->category,
                'bidang' => $e->bidang,
                'created_by' => $e->created_by,
                'can_manage' => $this->canManageEvent($e, $userRole, $userBidang),
                'recipients' => $e->reminderRecipients->pluck('role')->toArray(),
                'reminder_offset_days' => $e->reminder_offset_days,
            ];
        });

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $validated = $this->validateEvent($request);

        $event = AgendaEvent::create(array_merge($validated, [
            'created_by' => session('user')['role'] ?? 'unknown',
        ]));

        // Simpan recipient roles
        $recipients = [];
        if ($request->has('recipients') && is_array($request->recipients)) {
            foreach ($request->recipients as $role) {
                $user = User::where('role', $role)->first();
                if ($user && $user->email) {
                    $recipient = ReminderRecipient::create([
                        'agenda_event_id' => $event->id,
                        'role' => $role,
                        'email' => $user->email,
                    ]);
                    $recipients[] = $recipient;
                }
            }
        }

        // Kirim email reminder HANYA JIKA hari ini adalah hari reminder
        if (!empty($recipients) && $this->shouldSendReminderToday($event)) {
            $this->sendReminderEmailBrevo($event, $recipients);
        }

        return response()->json([
            'success' => true,
            'message' => 'Event berhasil ditambahkan' . (!empty($recipients) && $this->shouldSendReminderToday($event) ? ' 📧 Reminder dikirim' : ''),
            'event' => $event,
        ]);
    }

    public function update(Request $request, AgendaEvent $event)
    {
        $userRole = session('user')['role'] ?? null;
        $userBidang = session('user')['bidang'] ?? null;

        if (!$this->canManageEvent($event, $userRole, $userBidang)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk mengedit event ini.',
            ], 403);
        }

        $validated = $this->validateEvent($request);
        $event->update($validated);

        // Update recipients
        $recipients = [];
        if ($request->has('recipients')) {
            // Hapus recipients lama
            $event->reminderRecipients()->delete();
            
            // Tambahkan recipients baru
            foreach ($request->recipients as $role) {
                $user = User::where('role', $role)->first();
                if ($user && $user->email) {
                    $recipient = ReminderRecipient::create([
                        'agenda_event_id' => $event->id,
                        'role' => $role,
                        'email' => $user->email,
                    ]);
                    $recipients[] = $recipient;
                }
            }

            // Kirim email reminder HANYA JIKA hari ini adalah hari reminder
            if (!empty($recipients) && $this->shouldSendReminderToday($event)) {
                $this->sendReminderEmailBrevo($event, $recipients);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Event berhasil diperbarui' . (!empty($recipients) && $this->shouldSendReminderToday($event) ? ' 📧 Reminder dikirim' : ''),
            'event' => $event,
        ]);
    }

    /**
     * Cek apakah reminder harus dikirim hari ini
     */
    private function shouldSendReminderToday(AgendaEvent $event): bool
    {
        // Jika reminder_offset_days = -1 (tanpa reminder), jangan kirim
        if ($event->reminder_offset_days == -1) {
            return false;
        }

        // Hitung tanggal reminder
        $reminderDate = $event->start_date->copy()->subDays($event->reminder_offset_days);
        $today = Carbon::now()->startOfDay();
        
        // Kirim hanya jika hari ini adalah hari reminder
        return $reminderDate->isSameDay($today);
    }

    public function destroy(AgendaEvent $event)
    {
        $userRole = session('user')['role'] ?? null;
        $userBidang = session('user')['bidang'] ?? null;

        if (!$this->canManageEvent($event, $userRole, $userBidang)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk menghapus event ini.',
            ], 403);
        }

        $event->reminderRecipients()->delete();
        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Event berhasil dihapus',
        ]);
    }

    /**
     * Get users by role for dropdown
     */
    public function getUsersByRole(Request $request)
    {
        $roles = User::select('role', 'email', 'username')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('role')
            ->get()
            ->groupBy('role')
            ->map(function ($users) {
                return $users->map(function ($user) {
                    return [
                        'role' => $user->role,
                        'email' => $user->email,
                        'username' => $user->username,
                    ];
                })->toArray();
            });

        return response()->json($roles);
    }

    /**
     * Send reminder email using Brevo
     */
    private function sendReminderEmailBrevo(AgendaEvent $event, $recipients)
    {
        try {
            // Generate HTML content
            $html = view('emails.reminder', [
                'event' => $event,
                'recipients' => $recipients,
            ])->render();

            $subject = '📅 Reminder: ' . $event->title;

            $attachments = [];

            $allSuccess = true;
            foreach ($recipients as $recipient) {
                try {
                    \App\Services\BrevoMailService::send(
                        $recipient->email,
                        $recipient->email,
                        $subject,
                        $html,
                        $attachments
                    );

                    Log::info('Reminder email sent via Brevo', [
                        'event_id' => $event->id,
                        'email' => $recipient->email,
                        'role' => $recipient->role,
                    ]);

                } catch (\Throwable $e) {
                    Log::error('Failed to send reminder email via Brevo', [
                        'event_id' => $event->id,
                        'email' => $recipient->email,
                        'error' => $e->getMessage(),
                    ]);
                    $allSuccess = false;
                }
            }

            // Tandai sudah dikirim jika semua berhasil
            if ($allSuccess) {
                $event->update(['reminder_sent' => true]);
            }

        } catch (\Throwable $e) {
            Log::error('Failed to render reminder email', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send reminder email using Laravel Mail (fallback)
     */
    private function sendReminderEmail(AgendaEvent $event)
    {
        $recipients = $event->reminderRecipients;
        
        foreach ($recipients as $recipient) {
            try {
                Mail::send('emails.reminder', [
                    'event' => $event,
                    'recipient' => $recipient,
                ], function ($message) use ($recipient, $event) {
                    $message->to($recipient->email)
                            ->subject('Reminder: ' . $event->title);
                });
                
                Log::info('Reminder email sent', [
                    'event_id' => $event->id,
                    'email' => $recipient->email,
                ]);
            } catch (\Exception $e) {
                Log::error('Email reminder gagal dikirim: ' . $e->getMessage(), [
                    'event_id' => $event->id,
                    'email' => $recipient->email,
                ]);
            }
        }
    }

    private function canManageEvent(AgendaEvent $event, $userRole, $userBidang): bool
    {
        if ($userRole === 'Sekretaris') {
            return true;
        }

        return $event->created_by === $userRole;
    }

    private function validateEvent(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:255',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'category' => 'required|string|max:100',
            'bidang' => 'nullable|string|in:' . implode(',', AgendaEvent::BIDANG_OPTIONS),
            'reminder_offset_days' => 'nullable|integer|min:-1',
            'recipients' => 'nullable|array',
            'recipients.*' => 'string',
        ]);
    }
}