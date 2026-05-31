<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['review_id', 'reporter_id', 'reason', 'note', 'status'])]
class ReviewReport extends Model
{
    /** @use HasFactory<\Database\Factories\ReviewReportFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_RESOLVED_HIDE = 'resolved_hide';
    public const STATUS_RESOLVED_KEEP = 'resolved_keep';

    public const REASONS = ['spam', 'offensive', 'misleading', 'other'];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
