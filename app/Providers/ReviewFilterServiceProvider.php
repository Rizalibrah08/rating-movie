<?php

namespace App\Providers;

use App\Services\ReviewFilter\Pipeline;
use App\Services\ReviewFilter\Rules\BlacklistKeywordRule;
use App\Services\ReviewFilter\Rules\ContentDuplicateRule;
use App\Services\ReviewFilter\Rules\CooldownRule;
use App\Services\ReviewFilter\Rules\HourlyQuotaRule;
use App\Services\ReviewFilter\Rules\LengthRule;
use App\Services\ReviewFilter\Rules\OnePerMovieRule;
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
     * 4. OnePerMovie, Cooldown, ContentDuplicate — DB query frekuensi (reject)
     * 5. HourlyQuota — DB query terakhir (flag, bukan reject) — diletakkan terakhir
     *    supaya tidak menutupi rule reject lainnya.
     */
    public function register(): void
    {
        $this->app->singleton(Pipeline::class, function () {
            return (new Pipeline)
                ->pipe(new LengthRule(minChars: 30, minWords: 5))
                ->pipe(new VowelRule)
                ->pipe(new UrlDetectionRule)
                ->pipe(new BlacklistKeywordRule)
                ->pipe(new OnePerMovieRule)
                ->pipe(new CooldownRule(cooldownSeconds: 60))
                ->pipe(new ContentDuplicateRule)
                ->pipe(new HourlyQuotaRule(threshold: 5, windowMinutes: 60));
        });
    }

    public function boot(): void
    {
        //
    }
}
