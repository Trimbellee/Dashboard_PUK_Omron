<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoMailService
{
    protected const API_URL            = 'https://api.brevo.com/v3/smtp/email';
    protected const TIMEOUT            = 5;   // ← dikurangi dari 15 (cegah PHP timeout)
    protected const MAX_ATTEMPTS       = 2;   // ← dikurangi dari 3
    protected const MAX_ATTACHMENT_MB  = 20;

    public static function send(
        string $toEmail,
        ?string $toName,
        string $subject,
        string $htmlContent,
        array $attachments = [],
        array $cc  = [],
        array $bcc = []
    ) {
        try {
            if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                Log::warning('Brevo invalid email', ['email' => $toEmail]);
                return null;
            }

            $apiKey = config('services.brevo.key');

            if (!$apiKey) {
                Log::critical('Brevo API key missing');
                return null;
            }

            $payload = [
                'sender' => [
                    'name'  => config('mail.from.name'),
                    'email' => config('mail.from.address'),
                ],
                'to'          => [['email' => $toEmail, 'name' => $toName ?: $toEmail]],
                'subject'     => $subject ?: '(No Subject)',
                'htmlContent' => $htmlContent,
            ];

            if ($cc)          $payload['cc']         = $cc;
            if ($bcc)         $payload['bcc']        = $bcc;
            if ($attachments) $payload['attachment'] = array_filter($attachments);

            $attempt = 0;

            while ($attempt < self::MAX_ATTEMPTS) {
                $attempt++;

                try {
                    $response = Http::timeout(self::TIMEOUT)
                        ->withHeaders([
                            'api-key'      => $apiKey,
                            'Content-Type' => 'application/json',
                            'Accept'       => 'application/json',
                        ])
                        ->post(self::API_URL, $payload);

                    if ($response->successful()) {
                        Log::info('Brevo email sent', [
                            'to'      => $toEmail,
                            'subject' => $subject,
                            'attempt' => $attempt,
                        ]);
                        return $response;
                    }

                    Log::warning('Brevo send failed', [
                        'attempt' => $attempt,
                        'status'  => $response->status(),
                        'body'    => $response->body(),
                    ]);

                } catch (\Illuminate\Http\Client\ConnectionException $ce) {
                    // Koneksi gagal / timeout — log dan lanjut ke attempt berikutnya
                    Log::warning('Brevo connection error', [
                        'attempt' => $attempt,
                        'error'   => $ce->getMessage(),
                    ]);
                }

                if ($attempt < self::MAX_ATTEMPTS) {
                    usleep(200000); // 0.2 detik antar attempt
                }
            }

            Log::error('Brevo email permanent failure', [
                'to'      => $toEmail,
                'subject' => $subject,
            ]);

        } catch (\Throwable $e) {
            Log::critical('Brevo exception', ['message' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Kirim Mailable Laravel.
     * Gunakan render() saja — JANGAN panggil build() manual,
     * karena render() sudah memanggil build() secara internal.
     */
    public static function sendMailable(
        string $toEmail,
        ?string $toName,
        $mailable,
        array $attachments = []
    ) {
        try {
            // render() memanggil build() secara internal — tidak perlu $mailable->build()
            $html    = $mailable->render();
            $subject = $mailable->subject ?? '(No Subject)';

            return self::send($toEmail, $toName, $subject, $html, $attachments);

        } catch (\Throwable $e) {
            Log::error('Brevo sendMailable error', [
                'to'    => $toEmail,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Helper: attach dari path file
     */
    public static function makeAttachmentFromPath(string $path, string $name = null)
    {
        if (!file_exists($path)) return null;

        $sizeMB = filesize($path) / 1024 / 1024;

        if ($sizeMB > self::MAX_ATTACHMENT_MB) {
            Log::warning('Attachment terlalu besar', ['file' => $path, 'size' => $sizeMB]);
            return null;
        }

        return [
            'name'    => $name ?: basename($path),
            'content' => chunk_split(base64_encode(file_get_contents($path))),
        ];
    }

    /**
     * Helper: attach dari memory (DomPDF, dll)
     */
    public static function makeAttachmentFromContent(string $content, string $name)
    {
        return [
            'name'    => $name,
            'content' => chunk_split(base64_encode($content)),
        ];
    }
}