<?php

namespace App\Services\ReviewFilter;

/**
 * Hasil pemeriksaan satu rule oleh ReviewFilterPipeline.
 *
 * - passed = true → rule lolos (lanjut ke rule berikutnya)
 * - passed = false → rule fail. severity menentukan tindakan controller:
 *     - 'reject' → tolak ulasan, return 422
 *     - 'flag'   → simpan dengan status pending, masuk antrian moderasi
 */
final readonly class RuleResult
{
    public const SEVERITY_REJECT = 'reject';
    public const SEVERITY_FLAG = 'flag';

    public function __construct(
        public bool $passed,
        public ?string $reason = null,
        public string $severity = self::SEVERITY_REJECT,
        public ?string $rule = null,
    ) {}

    public static function pass(): self
    {
        return new self(passed: true);
    }

    public static function reject(string $rule, string $reason): self
    {
        return new self(
            passed: false,
            reason: $reason,
            severity: self::SEVERITY_REJECT,
            rule: $rule,
        );
    }

    public static function flag(string $rule, string $reason): self
    {
        return new self(
            passed: false,
            reason: $reason,
            severity: self::SEVERITY_FLAG,
            rule: $rule,
        );
    }
}
