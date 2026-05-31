<?php

namespace App\Services\ReviewFilter\Rules;

use App\Models\Review;
use App\Services\ReviewFilter\Contracts\ReviewRule;
use App\Services\ReviewFilter\ReviewContext;
use App\Services\ReviewFilter\RuleResult;

/**
 * OnePerMovieRule — satu user hanya boleh punya 1 ulasan published/pending per film.
 * Ulasan yang sudah ditolak (rejected) atau soft-deleted tidak menghalangi resubmit.
 */
final class OnePerMovieRule implements ReviewRule
{
    public function name(): string
    {
        return 'one_per_movie';
    }

    public function check(ReviewContext $ctx): RuleResult
    {
        if (! $ctx->userId) {
            return RuleResult::pass();
        }

        $exists = Review::query()
            ->where('user_id', $ctx->userId)
            ->where('movie_id', $ctx->movieId)
            ->whereIn('status', [Review::STATUS_PUBLISHED, Review::STATUS_PENDING])
            ->exists();

        if ($exists) {
            return RuleResult::reject(
                $this->name(),
                'Kamu sudah pernah memberi ulasan untuk film ini.',
            );
        }

        return RuleResult::pass();
    }
}
