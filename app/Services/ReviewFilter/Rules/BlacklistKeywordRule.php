<?php

namespace App\Services\ReviewFilter\Rules;

use App\Models\BlockedKeyword;
use App\Services\ReviewFilter\Contracts\ReviewRule;
use App\Services\ReviewFilter\ReviewContext;
use App\Services\ReviewFilter\RuleResult;

/**
 * BlacklistKeywordRule — periksa body normalisasi terhadap kata kunci aktif di blocked_keywords.
 *
 * Pakai word-boundary regex (`\b...\b`) supaya kata pendek seperti "racun" tidak salah-cocok dengan
 * "kacumacuna" (mitigasi PRD weakness #10).
 *
 * Mendukung deteksi negasi sederhana (PRD weakness #5): jika "tidak/bukan/nggak" muncul tepat
 * sebelum keyword, lewati pelanggaran (mis. "film ini tidak menjijikkan" → bukan pelanggaran).
 */
final class BlacklistKeywordRule implements ReviewRule
{
    private const NEGATION_WORDS = ['tidak', 'bukan', 'nggak', 'gak', 'enggak', 'tak'];

    public function name(): string
    {
        return 'blacklist_keyword';
    }

    public function check(ReviewContext $ctx): RuleResult
    {
        $keywords = BlockedKeyword::activeList();
        if (empty($keywords)) {
            return RuleResult::pass();
        }

        $body = $ctx->normalizedBody;

        foreach ($keywords as $entry) {
            $kw = $entry['keyword'];
            // Build word-boundary regex untuk frasa multi-kata juga
            $escaped = preg_quote($kw, '/');
            // Multi-kata: kw boleh dipisah whitespace fleksibel
            $pattern = '/\b'.str_replace(' ', '\s+', $escaped).'\b/u';

            if (! preg_match_all($pattern, $body, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            // Untuk tiap match, cek apakah didahului negasi
            foreach ($matches[0] as $match) {
                [$matched, $offset] = $match;
                if ($this->precededByNegation($body, $offset)) {
                    continue;
                }

                return RuleResult::reject(
                    $this->name(),
                    "Ulasan mengandung kata terlarang: \"{$kw}\".",
                );
            }
        }

        return RuleResult::pass();
    }

    /**
     * True jika 1-3 kata sebelum offset adalah salah satu negation word.
     */
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
