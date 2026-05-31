<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'movie_id',
    'review_id',
    'rule_triggered',
    'action',
    'reason',
    'payload_excerpt',
    'ip',
])]
class ReviewAuditLog extends Model
{
    public const ACTION_PUBLISHED = 'published';
    public const ACTION_PENDING = 'pending';
    public const ACTION_REJECTED = 'rejected';

    /** Audit log tidak punya updated_at — hanya created_at. */
    public const UPDATED_AT = null;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
