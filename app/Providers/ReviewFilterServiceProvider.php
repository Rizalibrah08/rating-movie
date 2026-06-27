<?php

namespace App\Providers;

use App\Services\ReviewFilter\Pipeline;
use App\Services\ReviewFilter\Rules\BlacklistKeywordRule;
use App\Services\ReviewFilter\Rules\ContentDuplicateRule;
use App\Services\ReviewFilter\Rules\CooldownRule;
use App\Services\ReviewFilter\Rules\HourlyQuotaRule;
use App\Services\ReviewFilter\Rules\IpRateLimitRule;
use App\Services\ReviewFilter\Rules\LengthRule;
use App\Services\ReviewFilter\Rules\OnePerMovieRule;
use App\Services\ReviewFilter\Rules\SeparatedCharsRule;
use App\Services\ReviewFilter\Rules\UrlDetectionRule;
use App\Services\ReviewFilter\Rules\VowelRule;
use Illuminate\Support\ServiceProvider;

class ReviewFilterServiceProvider extends ServiceProvider
{
    /**
     * Daftarkan Pipeline sebagai singleton supaya semua route share rule chain yang sama.
     *
     * Urutan rule penting:
     * 1. Length, Vowel — cek struktur dasar dulu (cepat, no DB)
     * 2. URL detection — pattern sederhana, sebelum keyword check (lebih cepat dipasangkan)
     * 3. Blacklist keyword — DB lookup pakai cache 5 menit
     * 4. SeparatedChars — deteksi bypass spasi antar huruf ("j e l e k")
     * 5. OnePerMovie, Cooldown, ContentDuplicate — DB query frekuensi (reject)
     * 6. HourlyQuota — DB query (flag, bukan reject)
     * 7. IpRateLimit — cache-based per-IP throttle (flag/reject terakhir)
     */
    public function register(): void
    {
        $this->app->singleton(Pipeline::class, function () {
            return (new Pipeline)
                ->pipe(new LengthRule(minChars: 30, minWords: 5))
                ->pipe(new VowelRule)
                ->pipe(new UrlDetectionRule)
                ->pipe(new BlacklistKeywordRule)
                ->pipe(new SeparatedCharsRule)
                ->pipe(new OnePerMovieRule)
                ->pipe(new CooldownRule(cooldownSeconds: 60))
                ->pipe(new ContentDuplicateRule)
                ->pipe(new HourlyQuotaRule(threshold: 5, windowMinutes: 60))
                ->pipe(new IpRateLimitRule(flagThreshold: 10, rejectThreshold: 20, windowMinutes: 60));
        });
    }

    public function boot(): void
    {
        //
    }
}
