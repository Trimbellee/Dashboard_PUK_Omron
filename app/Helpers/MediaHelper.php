<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MediaHelper
{
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
            $res = Http::timeout(6)->get('https://www.tiktok.com/oembed', ['url' => $url]);
            return $res->successful() ? $res->json() : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function getTikTokEmbedUrl(string $videoId): string
    {
        return "https://www.tiktok.com/player/v1/{$videoId}?rel=0";
    }

    /**
     * Thumbnail oEmbed TikTok berupa signed URL yang cepat kedaluwarsa dan panjang,
     * jadi diunduh dan disimpan lokal di storage/app/public/gallery-thumbs.
     * Mengembalikan URL publik lokal, atau string kosong jika gagal.
     */
    public static function storeTikTokThumbnail(?string $thumbUrl, string $videoId): string
    {
        if (!$thumbUrl) {
            return '';
        }

        // Batasi hanya https dan host milik TikTok/CDN-nya
        $parts = parse_url($thumbUrl);
        $host  = strtolower($parts['host'] ?? '');
        if (($parts['scheme'] ?? '') !== 'https'
            || !preg_match('/(tiktok|tiktokcdn|ibyteimg|byteimg)/', $host)) {
            return '';
        }

        try {
            $res = Http::timeout(8)->get($thumbUrl);
            if ($res->successful() && $res->body() !== '') {
                $path = "gallery-thumbs/tiktok-{$videoId}.jpg";
                Storage::disk('public')->put($path, $res->body());
                return asset('storage/' . $path);
            }
        } catch (\Throwable $e) {
            // abaikan, pakai fallback
        }

        return '';
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
}