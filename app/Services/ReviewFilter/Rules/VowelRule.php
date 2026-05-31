<?php

namespace App\Services\ReviewFilter\Rules;

use App\Services\ReviewFilter\Contracts\ReviewRule;
use App\Services\ReviewFilter\ReviewContext;
use App\Services\ReviewFilter\RuleResult;

/**
 * VowelRule — ulasan harus mengandung setidaknya satu huruf vokal (a/i/u/e/o).
 *
 * Catatan: Aturan ini dari konteks_project.md tetap dipertahankan, tapi sengaja diperluas
 * (PRD weakness #3) untuk juga mendeteksi keyboard-mash murni seperti "asdkjasdkjasd"
 * yang punya banyak konsonan tanpa kata bermakna.
 */
final class VowelRule implements ReviewRule
{
    public function name(): string
    {
        return 'vowel';
    }

    public function check(ReviewContext $ctx): RuleResult
    {
        $body = mb_strtolower($ctx->body);

        // 1. Wajib ada vokal
        if (! preg_match('/[aiueo]/u', $body)) {
            return RuleResult::reject(
                $this->name(),
                'Ulasan harus mengandung setidaknya satu huruf vokal.',
            );
        }

        // 2. Mitigasi keyboard-mash: rasio konsonan-tanpa-vokal terlalu tinggi
        $alphaOnly = preg_replace('/[^a-z]/u', '', $body) ?? '';
        $alphaLen = mb_strlen($alphaOnly);
        if ($alphaLen >= 20) {
            $vowelCount = preg_match_all('/[aiueo]/u', $alphaOnly);
            $vowelRatio = $vowelCount / $alphaLen;
            // Rata-rata bahasa Indonesia dan Inggris ~35-45% vokal.
            // Threshold 0.10 = sangat agresif, hanya menangkap teks yang nyata-nyata mash.
            if ($vowelRatio < 0.10) {
                return RuleResult::reject(
                    $this->name(),
                    'Ulasan terlihat seperti ketikan acak (terlalu sedikit vokal).',
                );
            }
        }

        return RuleResult::pass();
    }
}
