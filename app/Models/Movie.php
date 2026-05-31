<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'title',
    'slug',
    'synopsis',
    'year',
    'duration_min',
    'director',
    'poster_path',
    'poster_url',
    'backdrop_path',
    'backdrop_url',
])]
class Movie extends Model
{
    /** @use HasFactory<\Database\Factories\MovieFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'duration_min' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Movie $movie) {
            if (empty($movie->slug) && ! empty($movie->title)) {
                $base = Str::slug($movie->title);
                $slug = $base;
                $i = 1;
                while (
                    static::query()
                        ->where('slug', $slug)
                        ->where('id', '!=', $movie->id ?? 0)
                        ->exists()
                ) {
                    $slug = $base.'-'.(++$i);
                }
                $movie->slug = $slug;
            }
        });
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function publishedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', 'published');
    }

    /**
     * Resolved poster URL — prefer uploaded file, fallback to external URL.
     */
    public function getPosterAttribute(): ?string
    {
        if (! empty($this->poster_path)) {
            return Storage::disk('public')->url($this->poster_path);
        }

        return $this->poster_url ?: null;
    }

    /**
     * Resolved backdrop URL — null jika tidak ada (fallback ke poster di FE).
     */
    public function getBackdropAttribute(): ?string
    {
        if (! empty($this->backdrop_path)) {
            return Storage::disk('public')->url($this->backdrop_path);
        }

        return $this->backdrop_url ?: null;
    }

    /**
     * True jika movie eligible untuk hero rotator (punya backdrop).
     */
    public function getHasBackdropAttribute(): bool
    {
        return ! empty($this->backdrop_path) || ! empty($this->backdrop_url);
    }
}
