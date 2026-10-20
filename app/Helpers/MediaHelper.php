<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MediaHelper
{
    /** Batas ukuran file mentah yang masih boleh disimpan jika resize GD gagal (byte). */
    private const THUMB_RAW_MAX_BYTES = 512000; // 500 KB

    /** Lebar maksimal thumbnail lokal (px) dan kualitas JPEG (0-100). */
    private const THUMB_MAX_WIDTH = 480;
    private const THUMB_QUALITY   = 70;

    /* ==========================================================
     |  YOUTUBE
     ========================================================== */

    public static function extractYouTubeId(string $url): ?string
    {
        $patterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/v\/|youtube\.com\/shorts\/)([^&?]+)/',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    public static function getYouTubeEmbedUrl(string $videoId): string
    {
        return "https://www.youtube.com/embed/{$videoId}";
    }

    public static function getYouTubeThumbnail(string $videoId): string
    {
        return "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg";
    }

    /* ==========================================================
     |  GOOGLE DRIVE
     ========================================================== */

    public static function extractGoogleDriveId(string $url): ?string
    {
        if (preg_match('/\/file\/d\/([^\/]+)/', $url, $matches)) {
            return $matches[1];
        }
        if (preg_match('/[?&]id=([^&]+)/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public static function getGoogleDriveEmbedUrl(string $fileId): string
    {
        return "https://drive.google.com/file/d/{$fileId}/preview";
    }

    /**
     * Endpoint thumbnail Drive (tidak resmi): hanya bekerja untuk file yang dibagikan publik
     * dan bisa berubah sewaktu-waktu. View memakai onerror -> placeholder sebagai cadangan.
     */
    public static function getGoogleDriveThumbnail(string $fileId): string
    {
        return "https://drive.google.com/thumbnail?id={$fileId}&sz=w640";
    }

    /* ==========================================================
     |  TIKTOK
     ========================================================== */

    /** Cocok untuk tiktok.com, www., vm., vt., m. */
    public static function isTikTokUrl(string $url): bool
    {
        return (bool) preg_match('#^https?://([\w-]+\.)?tiktok\.com/#i', $url);
    }

    /**
     * Ambil ID video dari URL panjang:
     *  - https://www.tiktok.com/@user/video/7301234567890123456
     *  - https://www.tiktok.com/embed/v2/7301234567890123456
     *  - https://www.tiktok.com/player/v1/7301234567890123456
     * Link pendek (vm.tiktok.com/xxx, tiktok.com/t/xxx) ditangani lewat oEmbed.
     */
    public static function extractTikTokId(string $url): ?string
    {
        if (preg_match('#tiktok\.com/(?:[^/]+/video|embed(?:/v2)?|player/v1)/(\d+)#i', $url, $m)) {
            return $m[1];
        }
        return null;
    }

    public static function fetchTikTokOembed(string $url): ?array
    {
        try {
            $res = Http::timeout(10)->get('https://www.tiktok.com/oembed', ['url' => $url]);

            if (!$res->successful()) {
                Log::warning('TikTok oEmbed gagal', ['status' => $res->status(), 'url' => $url]);
                return null;
            }

            $json = $res->json();

            // Status 2xx tapi isinya bukan JSON oEmbed yang valid (mis. halaman blokir/captcha,
            // pesan error, atau video privat) -> catat cuplikan isinya untuk diagnosis.
            if (!is_array($json) || empty($json['thumbnail_url'])) {
                Log::warning('TikTok oEmbed tanpa thumbnail_url', [
                    'status' => $res->status(),
                    'url'    => $url,
                    'body'   => mb_substr((string) $res->body(), 0, 300),
                ]);
            }

            return is_array($json) ? $json : null;
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    public static function getTikTokEmbedUrl(string $videoId): string
    {
        return "https://www.tiktok.com/player/v1/{$videoId}?rel=0";
    }

    /**
     * Thumbnail oEmbed TikTok berupa signed URL yang cepat kedaluwarsa,
     * jadi diunduh, DIKECILKAN (lebar maks 480px, JPEG q70, sekitar 15-30 KB)
     * dan disimpan lokal di storage/app/public/gallery-thumbs.
     * Mengembalikan URL publik lokal, atau string kosong jika gagal.
     */
    public static function storeTikTokThumbnail(?string $thumbUrl, string $videoId): string
    {
        if (!$thumbUrl) {
            return '';
        }

        $path = "gallery-thumbs/tiktok-{$videoId}.jpg";

        // Sudah pernah disimpan -> tidak perlu unduh ulang
        if (Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        // Batasi hanya https dan host milik TikTok/CDN-nya
        $parts = parse_url($thumbUrl);
        $host  = strtolower($parts['host'] ?? '');
        if (($parts['scheme'] ?? '') !== 'https'
            || !preg_match('/(tiktok|tiktokcdn|ibyteimg|byteimg)/', $host)) {
            Log::warning('Thumbnail TikTok ditolak: host tidak dikenali', ['host' => $host]);
            return '';
        }

        try {
            // Tanpa header kustom (sama seperti versi awal yang sudah terbukti jalan)
            $res = Http::timeout(10)->get($thumbUrl);

            if (!$res->successful() || $res->body() === '') {
                Log::warning('Unduh thumbnail TikTok gagal', ['status' => $res->status(), 'host' => $host]);
                return '';
            }

            $body   = $res->body();
            $binary = self::resizeToJpeg($body);

            if ($binary === null) {
                // GD tidak ada / format tidak terbaca: simpan apa adanya bila ukurannya wajar
                if (strlen($body) > self::THUMB_RAW_MAX_BYTES) {
                    Log::warning('Thumbnail TikTok tidak bisa dikecilkan dan terlalu besar', ['bytes' => strlen($body)]);
                    return '';
                }
                Log::info('Resize thumbnail TikTok dilewati (GD/format), file disimpan apa adanya', [
                    'gd'           => function_exists('imagecreatefromstring'),
                    'content_type' => $res->header('Content-Type'),
                ]);
                $binary = $body;
            }

            Storage::disk('public')->put($path, $binary);
            return asset('storage/' . $path);
        } catch (\Throwable $e) {
            report($e); // tercatat di storage/logs/laravel.log
        }

        return '';
    }

    /**
     * Ambil ulang thumbnail untuk video TikTok yang sudah tersimpan (hanya punya ID).
     * Mencoba beberapa bentuk URL karena oEmbed menerima URL video publik.
     * Mengembalikan URL lokal, atau string kosong jika gagal.
     */
    public static function refreshTikTokThumbnail(string $videoId): string
    {
        $candidates = [
            "https://www.tiktok.com/@_/video/{$videoId}",
            "https://www.tiktok.com/embed/v2/{$videoId}",
        ];

        foreach ($candidates as $candidate) {
            $oembed = self::fetchTikTokOembed($candidate);
            $remote = $oembed['thumbnail_url'] ?? self::scrapeTikTokCover($candidate);

            $local = self::storeTikTokThumbnail($remote, $videoId);
            if ($local !== '') {
                return $local;
            }
        }

        return '';
    }

    /**
     * Cadangan saat oEmbed tidak memberi thumbnail_url: baca halaman video dan ambil
     * og:image atau "cover" dari JSON halaman. Best-effort (bisa gagal jika TikTok
     * membatasi akses server); alasan kegagalan dicatat di log.
     */
    public static function scrapeTikTokCover(string $url): ?string
    {
        try {
            $res = Http::timeout(10)->withHeaders([
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])->get($url);

            if (!$res->successful()) {
                Log::warning('Scrape cover TikTok gagal', ['status' => $res->status(), 'url' => $url]);
                return null;
            }

            $html = $res->body();

            if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)
                || preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/i', $html, $m)) {
                return html_entity_decode($m[1]);
            }

            if (preg_match('/"cover":"(https:[^"]+)"/', $html, $m)) {
                $decoded = json_decode('"' . $m[1] . '"');
                if (is_string($decoded) && $decoded !== '') {
                    return $decoded;
                }
            }

            Log::warning('Cover TikTok tidak ditemukan di halaman video', ['url' => $url]);
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }

    /**
     * Simpan gambar thumbnail unggahan admin: dikecilkan (lebar maks 480px, JPEG q70).
     * Mengembalikan URL lokal, atau string kosong jika gambar tidak bisa diproses.
     */
    public static function storeUploadedThumbnail(\Illuminate\Http\UploadedFile $file): string
    {
        $binary = self::resizeToJpeg((string) $file->get());
        if ($binary === null) {
            Log::warning('Thumbnail unggahan tidak bisa diproses GD', ['mime' => $file->getMimeType()]);
            return '';
        }

        $path = 'gallery-thumbs/upload-' . \Illuminate\Support\Str::random(16) . '.jpg';
        Storage::disk('public')->put($path, $binary);

        return asset('storage/' . $path);
    }

    /**
     * Perkecil gambar ke JPEG. Mengembalikan null jika GD tidak tersedia
     * atau format gambar tidak terbaca (pemanggil yang menentukan fallback).
     */
    private static function resizeToJpeg(string $binary): ?string
    {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }

        $src = @imagecreatefromstring($binary);
        if (!$src) {
            return null;
        }

        $dst = $src;
        if (imagesx($src) > self::THUMB_MAX_WIDTH) {
            $scaled = imagescale($src, self::THUMB_MAX_WIDTH);
            if ($scaled) {
                $dst = $scaled;
            }
        }

        ob_start();
        imagejpeg($dst, null, self::THUMB_QUALITY);
        $jpg = ob_get_clean();

        imagedestroy($src);
        if ($dst !== $src) {
            imagedestroy($dst);
        }

        return ($jpg !== false && $jpg !== '') ? $jpg : null;
    }

    /* ==========================================================
     |  PROSES URL
     ========================================================== */

    /**
     * @param bool $storeThumbnail  false = jangan unduh thumbnail TikTok
     *                              (dipakai untuk endpoint preview AJAX).
     */
    public static function processUrl(string $url, bool $storeThumbnail = true): ?array
    {
        $url = trim($url);

        // --- YouTube ---
        $ytId = self::extractYouTubeId($url);
        if ($ytId) {
            return [
                'source'        => 'youtube',
                'type'          => 'video', // default, akan di-override admin
                'external_id'   => $ytId,
                'embed_url'     => self::getYouTubeEmbedUrl($ytId),
                'thumbnail_url' => self::getYouTubeThumbnail($ytId),
            ];
        }

        // --- TikTok (dicek sebelum Google Drive supaya "?id=" tidak salah tangkap) ---
        if (self::isTikTokUrl($url)) {
            $oembed = self::fetchTikTokOembed($url);

            $id = self::extractTikTokId($url)
                ?? ($oembed['embed_product_id'] ?? null);

            // fallback: ambil dari atribut html oEmbed (link pendek)
            if (!$id && !empty($oembed['html'])
                && preg_match('/data-video-id="(\d+)"/', $oembed['html'], $m)) {
                $id = $m[1];
            }

            if ($id) {
                $remoteThumb = $oembed['thumbnail_url'] ?? null;

                // oEmbed TikTok kini sering membalas tanpa thumbnail_url -> coba ambil cover dari halaman video
                if (!$remoteThumb && $storeThumbnail) {
                    $remoteThumb = self::scrapeTikTokCover($url);
                }

                return [
                    'source'        => 'tiktok',
                    'type'          => 'video',
                    'external_id'   => (string) $id,
                    'embed_url'     => self::getTikTokEmbedUrl((string) $id),
                    'thumbnail_url' => $storeThumbnail
                        ? self::storeTikTokThumbnail($remoteThumb, (string) $id)
                        : ($remoteThumb ?? ''),
                ];
            }

            return null; // URL TikTok tapi ID tidak bisa ditemukan
        }

        // --- Google Drive ---
        $gdId = self::extractGoogleDriveId($url);
        if ($gdId) {
            return [
                'source'        => 'googledrive',
                'type'          => 'image', // default, akan di-override admin
                'external_id'   => $gdId,
                'embed_url'     => self::getGoogleDriveEmbedUrl($gdId),
                'thumbnail_url' => self::getGoogleDriveThumbnail($gdId), // admin bisa menimpa manual
            ];
        }

        return null;
    }

    public static function extractGoogleDriveFolderId(string $url): ?string
{
    if (preg_match('#/folders/([\w-]+)#', $url, $m)) return $m[1];
    if (preg_match('#drive\.google\.com/.*[?&]id=([\w-]+)#', $url, $m)) return $m[1];
    return null;
}

/** Daftar file gambar di folder (publik). Return [['id'=>..., 'name'=>...], ...] atau null jika gagal. */
public static function listGoogleDriveFolderImages(string $folderId): ?array
{
    $key = config('services.google_drive.key');
    if (!$key) return null;

    $files = [];
    $pageToken = null;

    try {
        do {
            $res = Http::timeout(15)->get('https://www.googleapis.com/drive/v3/files', array_filter([
                'q'         => "'{$folderId}' in parents and mimeType contains 'image/' and trashed = false",
                'fields'    => 'nextPageToken, files(id, name)',
                'pageSize'  => 200,
                'orderBy'   => 'name',
                'pageToken' => $pageToken,
                'key'       => $key,
            ]));

            if (!$res->successful()) {
                Log::warning('List folder Drive gagal', ['status' => $res->status(), 'body' => mb_substr($res->body(), 0, 300)]);
                return null;
            }

            $files = array_merge($files, $res->json('files', []));
            $pageToken = $res->json('nextPageToken');
        } while ($pageToken);
    } catch (\Throwable $e) {
        report($e);
        return null;
    }

    return $files;
}

}