<?php

namespace App\Services\ReviewFilter\Rules;

use App\Models\Review;
use App\Services\ReviewFilter\Contracts\ReviewRule;
use App\Services\ReviewFilter\ReviewContext;
use App\Services\ReviewFilter\RuleResult;

/**
 * CooldownRule — user harus jeda minimal 60 detik antar submit ulasan.
 *
 * Mencegah burst submission yang menjadi indikator bot.
 */
final class CooldownRule implements ReviewRule
{
    public function __construct(
        private int $cooldownSeconds = 60,
    ) {}

    public function name(): string
    {
        return 'cooldown';
    }

    public function check(ReviewContext $ctx): RuleResult
    {
        if (! $ctx->userId) {
            return RuleResult::pass();
        }

        $latest = Review::query()
            ->where('user_id', $ctx->userId)
            ->latest('created_at')
            ->first(['created_at']);

        if (! $latest) {
            return RuleResult::pass();
        }

        $secondsAgo = $latest->created_at->diffInSeconds(now(), absolute: true);
        if ($secondsAgo < $this->cooldownSeconds) {
            $remaining = $this->cooldownSeconds - $secondsAgo;

            return RuleResult::reject(
                $this->name(),
                "Tunggu {$remaining} detik lagi sebelum submit ulasan baru.",
            );
        }

        return RuleResult::pass();
    }
}
