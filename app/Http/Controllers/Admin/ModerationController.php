<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewAuditLog;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ModerationController extends Controller
{
    public function index(): Response
    {
        $reviews = Review::query()
            ->where('status', Review::STATUS_PENDING)
            ->with(['user:id,name,email', 'movie:id,title,slug,poster_path,poster_url'])
            ->latest()
            ->paginate(20)
            ->through(function (Review $r) {
                // Ambil rule yang trigger pending dari audit log terkini
                $log = ReviewAuditLog::query()
                    ->where('review_id', $r->id)
                    ->latest('created_at')
                    ->first();

                return [
                    'id' => $r->id,
                    'rating' => $r->rating,
                    'body' => $r->body,
                    'created_at' => $r->created_at?->toISOString(),
                    'ip' => $r->ip,
                    'user' => $r->user ? [
                        'id' => $r->user->id,
                        'name' => $r->user->name,
                        'email' => $r->user->email,
                    ] : null,
                    'movie' => $r->movie ? [
                        'id' => $r->movie->id,
                        'title' => $r->movie->title,
                        'slug' => $r->movie->slug,
                        'poster' => $r->movie->poster,
                    ] : null,
                    'rule_triggered' => $log?->rule_triggered,
                    'reason' => $log?->reason,
                ];
            });

        return Inertia::render('admin/moderation/Index', [
            'reviews' => $reviews,
        ]);
    }

    public function approve(Review $review): RedirectResponse
    {
        if ($review->status !== Review::STATUS_PENDING) {
            return back()->with('flash.error', 'Hanya ulasan pending yang dapat di-approve.');
        }

        $review->update(['status' => Review::STATUS_PUBLISHED]);

        ReviewAuditLog::create([
            'user_id' => $review->user_id,
            'movie_id' => $review->movie_id,
            'review_id' => $review->id,
            'rule_triggered' => 'admin_approve',
            'action' => ReviewAuditLog::ACTION_PUBLISHED,
            'reason' => 'Disetujui admin: '.auth()->user()->name,
            'payload_excerpt' => mb_substr($review->body, 0, 200),
            'ip' => request()->ip(),
        ]);

        return back()->with('flash.success', 'Ulasan disetujui dan dipublikasikan.');
    }

    public function reject(Review $review): RedirectResponse
    {
        if ($review->status !== Review::STATUS_PENDING) {
            return back()->with('flash.error', 'Hanya ulasan pending yang dapat di-reject.');
        }

        $review->update(['status' => Review::STATUS_REJECTED]);

        ReviewAuditLog::create([
            'user_id' => $review->user_id,
            'movie_id' => $review->movie_id,
            'review_id' => $review->id,
            'rule_triggered' => 'admin_reject',
            'action' => ReviewAuditLog::ACTION_REJECTED,
            'reason' => 'Ditolak admin: '.auth()->user()->name,
            'payload_excerpt' => mb_substr($review->body, 0, 200),
            'ip' => request()->ip(),
        ]);

        return back()->with('flash.success', 'Ulasan ditolak.');
    }
}
