<?php

namespace App\Services\ReviewFilter\Rules;

use App\Services\ReviewFilter\Contracts\ReviewRule;
use App\Services\ReviewFilter\ReviewContext;
use App\Services\ReviewFilter\RuleResult;
use Illuminate\Support\Facades\Cache;

/**
 * IpRateLimitRule — rate limiting per IP address.
 *
 * Menutup celah yang tidak tertangani oleh HourlyQuotaRule (per user):
 * guest / multi-akun bisa spam dari IP yang sama.
 *
 * Default: max 10 submission per IP per jam → flag (bukan reject).
 * Jika melebihi 20 → reject langsung.
 */
final class IpRateLimitRule implements ReviewRule
{
    private const CACHE_PREFIX = 'ip_rate_limit:';

    public function __construct(
        private int $flagThreshold = 10,
        private int $rejectThreshold = 20,
        private int $windowMinutes = 60,
    ) {}

    public function name(): string
    {
        return 'ip_rate_limit';
    }

    public function check(ReviewContext $ctx): RuleResult
    {
        if (! $ctx->ip) {
            return RuleResult::pass();
        }

        $key = self::CACHE_PREFIX . md5($ctx->ip);
        $count = (int) Cache::get($key, 0);

        if ($count >= $this->rejectThreshold) {
            return RuleResult::reject(
                $this->name(),
                "Terlalu banyak ulasan dari IP ini. Coba lagi nanti.",
            );
        }

        if ($count >= $this->flagThreshold) {
            Cache::put($key, $count + 1, now()->addMinutes($this->windowMinutes));

            return RuleResult::flag(
                $this->name(),
                "Aktivitas tidak biasa dari IP ini — ulasan masuk antrian moderasi.",
            );
        }

        Cache::put($key, $count + 1, now()->addMinutes($this->windowMinutes));

        return RuleResult::pass();
    }
}
