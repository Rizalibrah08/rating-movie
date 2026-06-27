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

        $whitelists = array_filter($keywords, fn ($k) => $k['category'] === BlockedKeyword::CATEGORY_WHITELIST);
        $blacklists = array_filter($keywords, fn ($k) => $k['category'] !== BlockedKeyword::CATEGORY_WHITELIST);

        // Pre-process body: hapus frasa whitelist agar tidak memicu blacklist
        foreach ($whitelists as $wl) {
            $kw = $wl['keyword'];
            $isRegex = $wl['is_regex'] ?? false;
            if ($isRegex) {
                $pattern = '/' . trim($kw, '/') . '/ui';
                $body = preg_replace($pattern, '', $body);
            } else {
                $kw = \App\Services\ReviewFilter\TextNormalizer::normalize($kw);
                if ($kw !== '') {
                    $body = str_replace($kw, '', $body);
                }
            }
        }

        foreach ($blacklists as $entry) {
            $isRegex = $entry['is_regex'] ?? false;
            $kw = $entry['keyword'];

            if ($isRegex) {
                // Gunakan keyword sebagai regex langsung
                $pattern = '/' . trim($kw, '/') . '/ui';
                if (preg_match_all($pattern, $body, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[0] as $match) {
                        [$matched, $offset] = $match;
                        if ($this->precededByNegation($body, $offset)) continue;
                        return RuleResult::reject($this->name(), "Ulasan mengandung pola terlarang.");
                    }
                }
                continue;
            }

            // Keyword dari database harus dinormalisasi juga, 
            // supaya variasi seperti spasi, huruf besar, dsb konsisten dengan body.
            $kw = \App\Services\ReviewFilter\TextNormalizer::normalize($kw);
            
            if ($kw === '') {
                continue;
            }

            // 1. Exact Match via Regex Word-Boundary
            $escaped = preg_quote($kw, '/');
            $pattern = '/\b'.str_replace(' ', '\s+', $escaped).'\b/u';

            if (preg_match_all($pattern, $body, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    [$matched, $offset] = $match;
                    if ($this->precededByNegation($body, $offset)) {
                        continue;
                    }
                    return RuleResult::reject($this->name(), "Ulasan mengandung kata terlarang: \"{$kw}\".");
                }
            }

            // 2. Fuzzy Matching (Levenshtein) - khusus untuk single word keyword
            if (! str_contains($kw, ' ')) {
                $kwLen = mb_strlen($kw);
                if ($kwLen >= 4) { // Hanya fuzzy match untuk kata panjang
                    $words = preg_split('/\s+/u', $body, -1, PREG_SPLIT_OFFSET_CAPTURE) ?: [];
                    foreach ($words as $wordData) {
                        $word = $wordData[0];
                        $offset = $wordData[1];
                        
                        $distance = levenshtein($kw, $word);
                        $threshold = $kwLen >= 7 ? 2 : 1;

                        if ($distance <= $threshold && $distance > 0) { // match fuzzy
                            if ($this->precededByNegation($body, $offset)) continue;
                            return RuleResult::reject($this->name(), "Ulasan mengandung kata yang menyerupai: \"{$kw}\".");
                        }
                    }
                }
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
