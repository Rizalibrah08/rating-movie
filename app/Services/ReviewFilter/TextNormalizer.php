<?php

namespace App\Services\ReviewFilter;

use Illuminate\Support\Str;

/**
 * Helper normalisasi teks untuk filter pipeline.
 *
 * Tujuan: gagalkan bypass trivial (mis. "j3l3k", "j e l e k", "JeLeK")
 * sebelum keyword matching. Sesuai weakness analysis #2 di PRD.
 */
final class TextNormalizer
{
    /** Mapping leetspeak basic → huruf normal. */
    private const LEETSPEAK_MAP = [
        '0' => 'o',
        '1' => 'i',
        '3' => 'e',
        '4' => 'a',
        '5' => 's',
        '7' => 't',
        '@' => 'a',
        '$' => 's',
    ];

    /**
     * Normalisasi teks untuk perbandingan keyword.
     *
     * Langkah:
     * 1. Lowercase
     * 2. Ganti leetspeak basic
     * 3. Trim & collapse whitespace
     * 4. Strip karakter non-alphanumerik (kecuali spasi & tanda baca dasar)
     */
    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = strtr($text, self::LEETSPEAK_MAP);
        // Strip karakter yang sering dipakai bypass (spasi di tengah kata seperti "j e l e k")
        // Tapi PERTAHANKAN spasi antar kata supaya word-boundary regex tetap bisa bekerja.
        // Solusi: kompres pengulangan karakter → "jjjjjelekkkk" → "jelek".
        $text = preg_replace('/(.)\1{2,}/u', '$1', $text);
        // Collapse whitespace
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }

    /**
     * Hash kanonis dari body untuk deteksi duplikat.
     * Lebih agresif: strip semua non-alphanumerik agar typo & spasi tidak bisa dipakai bypass.
     */
    public static function canonicalHash(string $text): string
    {
        $canon = self::normalize($text);
        $canon = preg_replace('/[^a-z0-9]/u', '', $canon);

        return hash('sha256', $canon);
    }

    /**
     * Hitung jumlah kata "bermakna" (≥ 2 huruf) dalam teks normal.
     * Dipakai LengthRule untuk mengganti "30 char" murni dengan "minimum 5 kata bermakna" (PRD weakness #4).
     */
    public static function meaningfulWordCount(string $text): int
    {
        $normalized = self::normalize($text);
        if ($normalized === '') {
            return 0;
        }

        return (int) Str::of($normalized)
            ->explode(' ')
            ->filter(fn (string $word) => mb_strlen($word) >= 2)
            ->count();
    }
}
