<?php

namespace App\Services\ReviewFilter\Rules;

use App\Models\Review;
use App\Services\ReviewFilter\Contracts\ReviewRule;
use App\Services\ReviewFilter\ReviewContext;
use App\Services\ReviewFilter\RuleResult;
use App\Services\ReviewFilter\TextNormalizer;

/**
 * ContentDuplicateRule — tolak ulasan yang teksnya sama persis (canonical hash) dengan
 * ulasan lain yang sama-sama dari user ini, di film mana pun (indikasi spam copy-paste).
 */
final class ContentDuplicateRule implements ReviewRule
{
    public function name(): string
    {
        return 'content_duplicate';
    }

    public function check(ReviewContext $ctx): RuleResult
    {
        if (! $ctx->userId) {
            return RuleResult::pass();
        }

        $hash = $ctx->bodyHash;

        // Ambil ulasan user dalam 30 hari terakhir (semua status, termasuk pending),
        // hitung canonical hash di app-side karena DB tidak menyimpan hash.
        $recent = Review::query()
            ->where('user_id', $ctx->userId)
            ->where('created_at', '>=', now()->subDays(30))
            ->get(['body']);

        foreach ($recent as $r) {
            if (TextNormalizer::canonicalHash($r->body) === $hash) {
                return RuleResult::reject(
                    $this->name(),
                    'Teks ulasan sama persis dengan ulasanmu sebelumnya.',
                );
            }
        }

        return RuleResult::pass();
    }
}
