<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image; // Intervention Image v3

class ImageOptimizer
{
    /**
     * Konversi & kompres UploadedFile ke .webp, lalu simpan ke disk.
     * Return path relatif (format sama seperti hasil $file->store()),
     * sehingga bisa langsung dipakai menggantikan pemanggilan
     * $request->file('gambar')->store(...) di controller manapun.
     *
     * @param UploadedFile $file      File gambar hasil upload (dari request)
     * @param string $directory      Folder tujuan di dalam disk, mis. 'berita', 'media'
     * @param string $disk           Nama disk Laravel, default 'public'
     * @param int $quality           Kualitas webp 0-100. 80 = tajam, ukuran jauh lebih kecil
     *                               dari JPG/PNG asli.
     * @param int|null $maxWidth     Kalau lebar gambar asli melebihi ini, di-scale down dulu
     *                               (menjaga aspect ratio) sebelum di-encode. null = tidak diresize.
     * @param string|null $titleForFilename
     *                               Kalau diisi (mis. judul berita), nama file dibuat dari
     *                               slug judul (maks 20 huruf) + timestamp, contoh:
     *                               "rapat-anggota-triwu-20260918_143201-a1b2c3.webp".
     *                               Kalau null, nama file tetap acak seperti sebelumnya
     *                               (dipakai untuk gambar tanpa konteks judul, mis. media
     *                               library editor Quill).
     */
    public static function storeAsWebp(
        UploadedFile $file,
        string $directory,
        string $disk = 'public',
        int $quality = 80,
        ?int $maxWidth = 1600,
        ?string $titleForFilename = null
    ): string {
        $image = Image::read($file);

        if ($maxWidth !== null && $image->width() > $maxWidth) {
            $image->scaleDown(width: $maxWidth);
        }

        $filename = self::buildFilename($titleForFilename) . '.webp';
        $path = trim($directory, '/') . '/' . $filename;

        $encoded = $image->toWebp($quality);

        Storage::disk($disk)->put($path, (string) $encoded);

        return $path;
    }

    /**
     * Sama seperti storeAsWebp(), tapi mengembalikan array berisi path
     * sekaligus ukuran file (bytes) dan mime type hasil kompresi — berguna
     * kalau kamu perlu menyimpan info tersebut ke database (contoh: tabel
     * Media pada media library editor Quill).
     *
     * @return array{path: string, size: int, mime_type: string}
     */
    public static function storeAsWebpWithMeta(
        UploadedFile $file,
        string $directory,
        string $disk = 'public',
        int $quality = 80,
        ?int $maxWidth = 1600,
        ?string $titleForFilename = null
    ): array {
        $path = self::storeAsWebp($file, $directory, $disk, $quality, $maxWidth, $titleForFilename);

        return [
            'path' => $path,
            'size' => Storage::disk($disk)->size($path),
            'mime_type' => 'image/webp',
        ];
    }

    /**
     * Bangun nama file (tanpa ekstensi) dari judul (opsional) + timestamp +
     * suffix acak pendek. Suffix acak tetap disertakan walau ada judul, untuk
     * menghindari tabrakan nama kalau ada dua upload dengan judul yang sama
     * persis di detik yang sama.
     */
    private static function buildFilename(?string $title): string
    {
        $timestamp = now()->format('Ymd_His');
        $unique = Str::random(6);

        if ($title) {
            // Str::limit(..., 20, '') memotong ke 20 karakter TANPA menambah
            // "..." di akhir (parameter ketiga = akhiran kosong), lalu
            // Str::slug() merapikan jadi format aman-untuk-nama-file
            // (huruf kecil, spasi & simbol jadi tanda hubung).
            $slug = Str::slug(Str::limit($title, 35, ''));

            if ($slug === '') {
                $slug = 'berita';
            }

            return $slug . '-' . $timestamp . '-' . $unique;
        }

        return 'img-' . $timestamp . '-' . $unique;
    }
}