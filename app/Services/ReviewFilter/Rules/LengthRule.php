<?php

namespace App\Services\ReviewFilter\Rules;

use App\Services\ReviewFilter\Contracts\ReviewRule;
use App\Services\ReviewFilter\ReviewContext;
use App\Services\ReviewFilter\RuleResult;
use App\Services\ReviewFilter\TextNormalizer;

/**
 * LengthRule — ulasan harus minimal 30 karakter (sesuai konteks_project.md)
 * DAN minimal 5 kata bermakna (mitigasi PRD weakness #4: spammer pad teks "aaaaaa...").
 */
final class LengthRule implements ReviewRule
{
    public function __construct(
        private int $minChars = 30,
        private int $minWords = 5,
    ) {}

    public function name(): string
    {
        return 'length';
    }

    public function check(ReviewContext $ctx): RuleResult
    {
        $charCount = mb_strlen(trim($ctx->body));
        if ($charCount < $this->minChars) {
            return RuleResult::reject(
                $this->name(),
                "Ulasan terlalu pendek (min {$this->minChars} karakter, kamu menulis {$charCount}).",
            );
        }

        $words = TextNormalizer::meaningfulWordCount($ctx->body);
        if ($words < $this->minWords) {
            return RuleResult::reject(
                $this->name(),
                "Ulasan harus mengandung setidaknya {$this->minWords} kata bermakna.",
            );
        }

        return RuleResult::pass();
    }
}
