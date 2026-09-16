<?php

namespace App\Helpers;

class MediaHelper
{
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

    public static function getYouTubeEmbedUrl(string $videoId): string
    {
        return "https://www.youtube.com/embed/{$videoId}";
    }

    public static function getGoogleDriveEmbedUrl(string $fileId): string
    {
        return "https://drive.google.com/file/d/{$fileId}/preview";
    }

    public static function getYouTubeThumbnail(string $videoId): string
    {
        return "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg";
    }

    public static function processUrl(string $url): ?array
    {
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

        $gdId = self::extractGoogleDriveId($url);
        if ($gdId) {
            return [
                'source'        => 'googledrive',
                'type'          => 'image', // default, akan di-override admin
                'external_id'   => $gdId,
                'embed_url'     => self::getGoogleDriveEmbedUrl($gdId),
                'thumbnail_url' => '', // admin isi manual atau placeholder
            ];
        }

        return null;
    }
}