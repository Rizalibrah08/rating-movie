<?php

namespace App\Services\ReviewFilter\Rules;

use App\Models\BlockedKeyword;
use App\Services\ReviewFilter\Contracts\ReviewRule;
use App\Services\ReviewFilter\ReviewContext;
use App\Services\ReviewFilter\RuleResult;

/**
 * SeparatedCharsRule — mendeteksi bypass spasi antar huruf tunggal.
 *
 * Menutup celah #1: "j e l e k", "m e n j i j i k k a n"
 *
 * Strategi:
 * 1. Deteksi pola (huruf-spasi) berulang ≥2 dalam body.
 * 2. Jika ada, "collapse" spasi → bentuk kata utuh.
 * 3. Cocokkan terhadap daftar blocked keywords.
 *
 * Ini perlu rule terpisah dari BlacklistKeywordRule karena TextNormalizer
 * harus mempertahankan spasi antar kata normal supaya word-boundary bekerja.
 */
final class SeparatedCharsRule implements ReviewRule
{
    private const NEGATION_WORDS = ['tidak', 'bukan', 'nggak', 'gak', 'enggak', 'tak'];

    public function name(): string
    {
        return 'separated_chars';
    }

    public function check(ReviewContext $ctx): RuleResult
    {
        $keywords = BlockedKeyword::activeList();
        if (empty($keywords)) {
            return RuleResult::pass();
        }

        $body = mb_strtolower($ctx->body, 'UTF-8');

        // Cari semua pola "huruf (spasi huruf)+" yang berdekatan
        preg_match_all(
            '/(?<!\w)([a-z](?:\s[a-z]){2,})(?!\w)/u',
            $body,
            $matches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE,
        );

        if (empty($matches)) {
            return RuleResult::pass();
        }

        foreach ($matches as $match) {
            $spaced = $match[0][0];          // "j e l e k"
            $offset = $match[0][1];
            $collapsed = str_replace(' ', '', $spaced); // "jelek"

            // Cek apakah collapse cocok dengan keyword
            foreach ($keywords as $entry) {
                if (mb_strtolower($entry['keyword']) === $collapsed) {
                    // Cek negasi sebelum kata
                    if ($this->precededByNegation($body, $offset)) {
                        continue;
                    }

                    return RuleResult::reject(
                        $this->name(),
                        "Ulasan mengandung kata terlarang yang dikamuflase: \"{$entry['keyword']}\".",
                    );
                }
            }
        }

        return RuleResult::pass();
    }

    private function precededByNegation(string $body, int $offset): bool
    {
        $before = mb_substr($body, max(0, $offset - 30), min(30, $offset));
        $words = preg_split('/\s+/u', trim($before)) ?: [];
        $tail = array_slice($words, -3);
        foreach ($tail as $w) {
            if (in_array($w, self::NEGATION_WORDS, true)) {
                return true;
            }
        }

        return false;
    }
}
