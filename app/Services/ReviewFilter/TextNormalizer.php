<?php

namespace App\Services\ReviewFilter;

use Illuminate\Support\Str;

/**
 * Helper normalisasi teks untuk filter pipeline.
 *
 * Tujuan: gagalkan bypass trivial dan non-trivial sebelum keyword matching.
 *
 * Bypass yang ditangani:
 *   #1  Spasi di tengah kata     → "j e l e k"       → "jelek"  (via collapseSpacedChars)
 *   #2  Separator antar huruf    → "j.e.l.e.k"        → "jelek"  (via stripCharSeparators)
 *   #3  Homoglyph Unicode        → "јеlеk" (Cyrillic) → "jelek"  (via HOMOGLYPH_MAP)
 *   #4  Leetspeak lanjutan       → "j€l€k"            → "jelek"  (via extended LEETSPEAK_MAP)
 *   #5  Pengulangan ganda        → "jeleek"            → "jelek"  (threshold ≥2)
 *   #6  Zero-width characters    → "je\u200Blek"       → "jelek"  (via stripInvisible)
 *   #7  Diacritics/accent        → "jélék"             → "jelek"  (via stripDiacritics)
 */
final class TextNormalizer
{
    /**
     * Mapping leetspeak — DIPERLUAS untuk menutup celah #4.
     * Urutan penting: simbol multi-char di depan, single-char di belakang.
     */
    private const LEETSPEAK_MAP = [
        // Extended multi-char
        '|-|' => 'h',
        '|_|' => 'u',
        '()'  => 'o',
        '><'  => 'x',
        // Single-char
        '@'   => 'a',
        '4'   => 'a',
        '8'   => 'b',
        '('   => 'c',
        '3'   => 'e',
        '€'   => 'e',
        '6'   => 'g',
        '#'   => 'h',
        '!'   => 'i',
        '1'   => 'i',
        '|'   => 'i',
        '0'   => 'o',
        '9'   => 'q',
        '$'   => 's',
        '5'   => 's',
        '7'   => 't',
        '+'   => 't',
        'v'   => 'v', // no-op, keep for clarity
        '%'   => 'x',
        '2'   => 'z',
    ];

    /**
     * Homoglyph map: karakter Unicode yang mirip huruf Latin — menutup celah #3.
     * Mencakup Cyrillic, Greek, dan look-alike lainnya.
     */
    private const HOMOGLYPH_MAP = [
        // Cyrillic
        'а' => 'a', 'А' => 'a',
        'е' => 'e', 'Е' => 'e',
        'о' => 'o', 'О' => 'o',
        'р' => 'p', 'Р' => 'p',
        'с' => 'c', 'С' => 'c',
        'х' => 'x', 'Х' => 'x',
        'і' => 'i', 'І' => 'i',
        'ј' => 'j', 'Ј' => 'j',
        'ѕ' => 's', 'Ѕ' => 's',
        'у' => 'y', 'У' => 'y',
        'к' => 'k', 'К' => 'k',
        'м' => 'm', 'М' => 'm',
        'т' => 't', 'Т' => 't',
        'н' => 'h', 'Н' => 'h',
        // Greek
        'α' => 'a', 'Α' => 'a',
        'ε' => 'e', 'Ε' => 'e',
        'ο' => 'o', 'Ο' => 'o',
        'ρ' => 'p', 'Ρ' => 'p',
        'ν' => 'v', 'Ν' => 'n',
        'κ' => 'k', 'Κ' => 'k',
        'τ' => 't', 'Τ' => 't',
        'υ' => 'u', 'Υ' => 'y',
        'ι' => 'i', 'Ι' => 'i',
        // Full-width Latin (e.g. "ｊｅｌｅｋ")
        'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd', 'ｅ' => 'e',
        'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i', 'ｊ' => 'j',
        'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n', 'ｏ' => 'o',
        'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's', 'ｔ' => 't',
        'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x', 'ｙ' => 'y',
        'ｚ' => 'z',
    ];

    /**
     * Normalisasi teks untuk perbandingan keyword.
     *
     * Pipeline:
     *   1. Lowercase
     *   2. Strip zero-width & invisible characters  (#6)
     *   3. Normalisasi homoglyph Unicode            (#3)
     *   4. Strip diacritics/accent                  (#7)
     *   5. Strip character separators               (#2)
     *   6. Collapse spaced-out chars                (#1)
     *   7. Apply extended leetspeak map             (#4)
     *   8. Compress repeated characters (≥2)        (#5)
     *   9. Collapse whitespace
     */
    public static function normalize(string $text): string
    {
        // 1. Lowercase
        $text = mb_strtolower($text, 'UTF-8');

        // 2. Strip zero-width & invisible characters (#6)
        // Zero-width space, ZWNJ, ZWJ, BOM, soft-hyphen, word-joiner, etc.
        $text = preg_replace(
            '/[\x{00AD}\x{034F}\x{061C}\x{115F}\x{1160}\x{17B4}\x{17B5}'
            . '\x{180B}-\x{180D}\x{200B}-\x{200F}\x{202A}-\x{202E}'
            . '\x{2060}-\x{2064}\x{2066}-\x{206F}\x{3164}\x{FE00}-\x{FE0F}'
            . '\x{FEFF}\x{FFA0}\x{1D173}-\x{1D17A}]/u',
            '',
            $text,
        ) ?? $text;

        // 3. Homoglyph normalization (#3)
        $text = strtr($text, self::HOMOGLYPH_MAP);

        // 4. Strip diacritics/accents (#7)
        // Decompose → strip combining marks → recompose
        if (function_exists('transliterator_transliterate')) {
            $normalized = transliterator_transliterate(
                'NFD; [:Nonspacing Mark:] Remove; NFC',
                $text
            );
            if ($normalized !== false) {
                $text = $normalized;
            }
        } else {
            // Fallback: manual common accents
            $text = strtr($text, [
                'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
                'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
                'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
                'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
                'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
                'ý' => 'y', 'ÿ' => 'y',
                'ç' => 'c', 'ñ' => 'n',
            ]);
        }

        // 5. Strip char-level separators: "j.e.l.e.k" → "jelek" (#2)
        // Pattern: single alpha char followed by separator(s) then another single alpha
        $text = preg_replace('/\b([a-z])[.\-_*\\\\\/]{1,3}(?=[a-z]\b)/u', '$1', $text);
        // Iterate once more for chains (e.g. j.e.l.e.k needs multiple passes or a smarter regex)
        $text = preg_replace('/([a-z])[.\-_*\\\\\/]{1,3}(?=[a-z])/u', '$1', $text);

        // 6. Collapse spaced-out chars: "j e l e k" → "jelek" (#1)
        // If there's a pattern of (char space){3,} treat it as spaced word
        $text = preg_replace_callback(
            '/(?<!\w)((?:[a-z] ){2,}[a-z])(?!\w)/u',
            fn ($m) => str_replace(' ', '', $m[1]),
            $text,
        );

        // 7. Extended leetspeak map (#4)
        $text = strtr($text, self::LEETSPEAK_MAP);

        // 8. Compress repeated chars (≥2 → 1) (#5)
        // "jeleeeek" → "jelek", "jeleek" → "jelek"
        $text = preg_replace('/(.)\1+/u', '$1', $text);

        // 9. Collapse whitespace
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
     * Dipakai LengthRule untuk mengganti "30 char" murni dengan "minimum 5 kata bermakna".
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
