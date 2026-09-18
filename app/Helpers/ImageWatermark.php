<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class ImageWatermark
{
    /**
     * Watermark teks ala media online
     */
    public static function apply(string $relativePath, string $text = 'Media PUK OMI'): void
    {
        if (!function_exists('imagecreatefromjpeg')) {
            return;
        }

        $fullPath = Storage::disk('public')->path($relativePath);

        if (!file_exists($fullPath)) {
            return;
        }

        $info = @getimagesize($fullPath);

        if (!$info) {
            return;
        }

        $mime = $info['mime'];

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($fullPath),
            'image/png'  => @imagecreatefrompng($fullPath),
            'image/webp' => function_exists('imagecreatefromwebp')
                ? @imagecreatefromwebp($fullPath)
                : null,
            default => null,
        };

        if (!$image) {
            return;
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        $width  = imagesx($image);
        $height = imagesy($image);

        /*
        |--------------------------------------------------------------------------
        | Font
        |--------------------------------------------------------------------------
        | FIX: path lama hardcode ke 'C:/Windows/Fonts/arial.ttf' — hanya ada di
        | Windows. Di server Linux (kebanyakan hosting production) path ini tidak
        | pernah ketemu, sehingga watermark diam-diam selalu jatuh ke fallback
        | font bitmap GD yang kasar tanpa ada tanda error apapun.
        |
        | Solusinya: bundel font TTF sendiri di dalam project (mis. di
        | resources/fonts/arial.ttf, commit ke repo) lalu rujuk lewat
        | resource_path() yang portable di OS manapun. Ini juga sekalian
        | menghindari ketergantungan pada font sistem yang mungkin tidak
        | ter-install di server manapun (Windows/Linux/mac).
        |--------------------------------------------------------------------------
        */

        $fontPath = resource_path('fonts/arial.ttf');

        // Fallback berjenjang kalau font belum sempat kamu taruh di resources/fonts:
        // masih coba beberapa lokasi umum sebelum benar-benar menyerah ke bitmap font.
        if (!file_exists($fontPath)) {
            $candidates = [
                'C:/Windows/Fonts/arial.ttf',                 // Windows (dev lokal)
                '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', // Linux umum (Debian/Ubuntu)
                '/usr/share/fonts/truetype/msttcorefonts/Arial.ttf',
            ];

            foreach ($candidates as $candidate) {
                if (file_exists($candidate)) {
                    $fontPath = $candidate;
                    break;
                }
            }
        }

        $useTtf = function_exists('imagettftext')
            && file_exists($fontPath);

        /*
        |--------------------------------------------------------------------------
        | Responsive Size
        |--------------------------------------------------------------------------
        */

        $padding = max(18, (int) round($width * 0.022));

        if ($useTtf) {
            $fontSize = max(12, (int) round($width / 58));

            $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);

            $textWidth = abs($bbox[2] - $bbox[0]);
            $textHeight = abs($bbox[7] - $bbox[1]);

            $textX = $width - $textWidth - $padding;
            $textY = $height - $padding;

            /*
            |--------------------------------------------------------------------------
            | Warna (lebih samar seperti media online)
            |--------------------------------------------------------------------------
            */

            $textColor = imagecolorallocatealpha($image, 255, 255, 255, 55);

            $shadowColor = imagecolorallocatealpha($image, 0, 0, 0, 95);

            /*
            |--------------------------------------------------------------------------
            | Shadow tipis
            |--------------------------------------------------------------------------
            */

            imagettftext(
                $image,
                $fontSize,
                0,
                $textX + 1,
                $textY + 1,
                $shadowColor,
                $fontPath,
                $text
            );

            /*
            |--------------------------------------------------------------------------
            | Teks utama
            |--------------------------------------------------------------------------
            */

            imagettftext(
                $image,
                $fontSize,
                0,
                $textX,
                $textY,
                $textColor,
                $fontPath,
                $text
            );

        } else {

            /*
            |--------------------------------------------------------------------------
            | Fallback jika TTF tidak tersedia
            |--------------------------------------------------------------------------
            */

            $gdFont = 5;

            $textWidth = imagefontwidth($gdFont) * strlen($text);

            $textHeight = imagefontheight($gdFont);

            $textX = $width - $textWidth - $padding;
            $textY = $height - $padding - $textHeight;

            $textColor = imagecolorallocatealpha($image, 255, 255, 255, 55);
            $shadowColor = imagecolorallocatealpha($image, 0, 0, 0, 95);

            imagestring(
                $image,
                $gdFont,
                $textX + 1,
                $textY + 1,
                $text,
                $shadowColor
            );

            imagestring(
                $image,
                $gdFont,
                $textX,
                $textY,
                $text,
                $textColor
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Simpan kembali
        |--------------------------------------------------------------------------
        | FIX: imagewebp() sekarang dijaga function_exists(), simetris dengan
        | penjagaan imagecreatefromwebp() di atas. Tanpa ini, di server yang GD-nya
        | tidak mendukung penulisan webp, proses akan fatal error dan bisa
        | meninggalkan file rusak di $fullPath.
        |--------------------------------------------------------------------------
        */

        switch ($mime) {

            case 'image/jpeg':
                imagejpeg($image, $fullPath, 90);
                break;

            case 'image/png':
                imagepng($image, $fullPath, 6);
                break;

            case 'image/webp':
                if (function_exists('imagewebp')) {
                    // Kualitas disamakan dengan ImageOptimizer (80) supaya tidak
                    // ada penurunan kualitas tambahan akibat dua kali encode
                    // dengan angka kualitas yang berbeda-beda.
                    imagewebp($image, $fullPath, 80);
                }
                break;
        }

        imagedestroy($image);
    }
}