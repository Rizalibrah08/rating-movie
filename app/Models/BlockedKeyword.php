<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

#[Fillable(['keyword', 'category', 'is_active'])]
class BlockedKeyword extends Model
{
    /** @use HasFactory<\Database\Factories\BlockedKeywordFactory> */
    use HasFactory;

    public const CATEGORY_SPAM = 'spam';
    public const CATEGORY_PROMOSI = 'promosi';
    public const CATEGORY_INSULT = 'insult';
    public const CATEGORY_SLANG = 'slang_negative';
    public const CATEGORY_OTHER = 'other';

    public const CATEGORIES = [
        self::CATEGORY_SPAM,
        self::CATEGORY_PROMOSI,
        self::CATEGORY_INSULT,
        self::CATEGORY_SLANG,
        self::CATEGORY_OTHER,
    ];

    /** Cache key untuk daftar keyword aktif yang dipakai di filter pipeline. */
    public const CACHE_KEY = 'blocked_keywords:active';
    public const CACHE_TTL_SECONDS = 300;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Normalisasi keyword: lowercase + trim, sebelum disimpan
        static::saving(function (BlockedKeyword $keyword) {
            $keyword->keyword = Str::of((string) $keyword->keyword)->lower()->trim()->toString();
        });

        // Invalidate cache saat ada perubahan
        $invalidate = function (BlockedKeyword $kw) {
            Cache::forget(self::CACHE_KEY);
        };
        static::saved($invalidate);
        static::deleted($invalidate);
    }

    /**
     * Ambil semua keyword aktif (cached).
     *
     * @return array<int, array{keyword: string, category: string}>
     */
    public static function activeList(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            return static::query()
                ->where('is_active', true)
                ->orderBy('keyword')
                ->get(['keyword', 'category'])
                ->map(fn ($k) => ['keyword' => $k->keyword, 'category' => $k->category])
                ->all();
        });
    }
}
