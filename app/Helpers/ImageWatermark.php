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
        */

        $fontPath = 'C:/Windows/Fonts/arial.ttf';

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
        */

        switch ($mime) {

            case 'image/jpeg':
                imagejpeg($image, $fullPath, 90);
                break;

            case 'image/png':
                imagepng($image, $fullPath, 6);
                break;

            case 'image/webp':
                imagewebp($image, $fullPath, 90);
                break;
        }

        imagedestroy($image);
    }
}