<?php

namespace App\Helpers;

use Mews\Purifier\Facades\Purifier;

/**
 * QuillSanitizer
 * 
 * Khusus menangani sanitasi konten HTML dari Quill editor.
 * Mempertahankan semua atribut yang diperlukan agar tampilan
 * tetap rapi setelah disimpan.
 */
class QuillSanitizer
{
    /**
     * Daftar atribut data-* yang harus dipertahankan
     */
    private const PRESERVED_DATA_ATTRIBUTES = [
        'data-list',
        'data-checked',
    ];

    /**
     * Sanitize Quill HTML content while preserving necessary attributes
     * 
     * @param string $html Konten HTML dari Quill editor
     * @return string Konten HTML yang sudah disanitasi
     */
    public static function sanitize(string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Ekstrak dan simpan atribut data-* sebelum sanitasi
        $preserved = self::extractDataAttributes($html);
        
        // Sanitasi dengan config quill
        $cleaned = Purifier::clean($html, 'quill');
        
        // Restore atribut data-* yang hilang
        $restored = self::restoreDataAttributes($cleaned, $preserved);
        
        return $restored;
    }

    /**
     * Ekstrak atribut data-* dari HTML sebelum sanitasi
     * 
     * @param string $html
     * @return array
     */
    private static function extractDataAttributes(string $html): array
    {
        $preserved = [];
        
        // Cari semua elemen li
        preg_match_all('/<li[^>]*>/i', $html, $matches, PREG_OFFSET_CAPTURE);
        
        foreach ($matches[0] as $match) {
            $tag = $match[0];
            $offset = $match[1];
            
            // Ekstrak atribut data-list dan data-checked
            $dataList = null;
            $dataChecked = null;
            
            if (preg_match('/data-list=["\']([^"\']*)["\']/i', $tag, $dataMatch)) {
                $dataList = $dataMatch[1];
            }
            if (preg_match('/data-checked=["\']([^"\']*)["\']/i', $tag, $dataMatch)) {
                $dataChecked = $dataMatch[1];
            }
            
            if ($dataList !== null || $dataChecked !== null) {
                $preserved[] = [
                    'offset' => $offset,
                    'data-list' => $dataList,
                    'data-checked' => $dataChecked,
                    'original' => $tag,
                ];
            }
        }
        
        return $preserved;
    }

    /**
     * Restore atribut data-* yang hilang setelah sanitasi
     * 
     * @param string $html HTML yang sudah disanitasi
     * @param array $preserved Data atribut yang disimpan
     * @return string
     */
    private static function restoreDataAttributes(string $html, array $preserved): string
    {
        if (empty($preserved)) {
            return $html;
        }

        $result = $html;
        $offsetAdjustment = 0;
        
        // Cari semua elemen li di HTML yang sudah disanitasi
        preg_match_all('/<li[^>]*>/i', $result, $matches, PREG_OFFSET_CAPTURE);
        
        foreach ($matches[0] as $index => $match) {
            if (!isset($preserved[$index])) {
                continue;
            }
            
            $currentTag = $match[0];
            $currentOffset = $match[1] + $offsetAdjustment;
            $preservedData = $preserved[$index];
            
            $newTag = $currentTag;
            $hasChanges = false;
            
            // Tambahkan data-list jika ada dan belum ada di tag saat ini
            if ($preservedData['data-list'] !== null && !preg_match('/data-list=["\']/', $newTag)) {
                $newTag = preg_replace('/<li/i', '<li data-list="' . $preservedData['data-list'] . '"', $newTag, 1);
                $hasChanges = true;
            }
            
            // Tambahkan data-checked jika ada dan belum ada di tag saat ini
            if ($preservedData['data-checked'] !== null && !preg_match('/data-checked=["\']/', $newTag)) {
                $newTag = preg_replace('/<li/i', '<li data-checked="' . $preservedData['data-checked'] . '"', $newTag, 1);
                $hasChanges = true;
            }
            
            if ($hasChanges && $newTag !== $currentTag) {
                $result = substr_replace($result, $newTag, $currentOffset, strlen($currentTag));
                $offsetAdjustment += strlen($newTag) - strlen($currentTag);
            }
        }
        
        return $result;
    }

    /**
     * Sanitize dengan fallback ke default jika config quill tidak ditemukan
     * 
     * @param string $html Konten HTML dari Quill editor
     * @return string Konten HTML yang sudah disanitasi
     */
    public static function sanitizeSafe(string $html): string
    {
        if (empty($html)) {
            return '';
        }

        try {
            return self::sanitize($html);
        } catch (\Exception $e) {
            // Fallback: sanitasi manual tanpa menghilangkan data-*
            return self::manualSanitize($html);
        }
    }

    /**
     * Sanitasi manual sebagai fallback
     * 
     * @param string $html
     * @return string
     */
    private static function manualSanitize(string $html): string
    {
        // Hapus tag script, style, on* events
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
        $html = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        
        return $html;
    }
}