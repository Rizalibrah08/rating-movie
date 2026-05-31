<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'movie_id', 'rating', 'body', 'status', 'ip'])]
class Review extends Model
{
    /** @use HasFactory<\Database\Factories\ReviewFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_PUBLISHED = 'published';
    public const STATUS_PENDING = 'pending';
    public const STATUS_REJECTED = 'rejected';

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Map rating 0-100 ke kategori warna Metacritic.
     * - 75-100 → "green" (positive)
     * - 50-74  → "yellow" (mixed)
     * - 0-49   → "red" (negative)
     */
    public function getScoreCategoryAttribute(): string
    {
        return match (true) {
            $this->rating >= 75 => 'green',
            $this->rating >= 50 => 'yellow',
            default => 'red',
        };
    }
}
