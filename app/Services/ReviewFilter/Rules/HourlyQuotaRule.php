<?php

namespace App\Services\ReviewFilter\Rules;

use App\Models\Review;
use App\Services\ReviewFilter\Contracts\ReviewRule;
use App\Services\ReviewFilter\ReviewContext;
use App\Services\ReviewFilter\RuleResult;

/**
 * HourlyQuotaRule — jika user sudah submit >= 5 ulasan dalam 1 jam terakhir,
 * tandai ulasan baru sebagai PENDING (severity 'flag', bukan reject) untuk
 * dimoderasi admin secara manual.
 *
 * Berbeda dari rule lain (yang reject), rule ini "soft" — tetap simpan tapi tunda publikasi.
 */
final class HourlyQuotaRule implements ReviewRule
{
    public function __construct(
        private int $threshold = 5,
        private int $windowMinutes = 60,
    ) {}

    public function name(): string
    {
        return 'hourly_quota';
    }

    public function check(ReviewContext $ctx): RuleResult
    {
        if (! $ctx->userId) {
            return RuleResult::pass();
        }

        $recentCount = Review::query()
            ->where('user_id', $ctx->userId)
            ->where('created_at', '>=', now()->subMinutes($this->windowMinutes))
            ->count();

        if ($recentCount >= $this->threshold) {
            return RuleResult::flag(
                $this->name(),
                "Aktivitas tinggi terdeteksi ({$recentCount} ulasan dalam {$this->windowMinutes} menit). Ulasan kamu akan ditinjau admin.",
            );
        }

        return RuleResult::pass();
    }
}
