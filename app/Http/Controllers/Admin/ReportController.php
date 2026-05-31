<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewAuditLog;
use App\Models\ReviewReport;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(): Response
    {
        $reports = ReviewReport::query()
            ->with([
                'reporter:id,name,email',
                'review:id,user_id,movie_id,rating,body,status',
                'review.user:id,name',
                'review.movie:id,title,slug,poster_path,poster_url',
            ])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(20)
            ->through(fn (ReviewReport $r) => [
                'id' => $r->id,
                'reason' => $r->reason,
                'note' => $r->note,
                'status' => $r->status,
                'created_at' => $r->created_at?->toISOString(),
                'reporter' => $r->reporter ? [
                    'id' => $r->reporter->id,
                    'name' => $r->reporter->name,
                    'email' => $r->reporter->email,
                ] : null,
                'review' => $r->review ? [
                    'id' => $r->review->id,
                    'rating' => $r->review->rating,
                    'body' => $r->review->body,
                    'status' => $r->review->status,
                    'author' => $r->review->user ? [
                        'id' => $r->review->user->id,
                        'name' => $r->review->user->name,
                    ] : null,
                    'movie' => $r->review->movie ? [
                        'id' => $r->review->movie->id,
                        'title' => $r->review->movie->title,
                        'slug' => $r->review->movie->slug,
                        'poster' => $r->review->movie->poster,
                    ] : null,
                ] : null,
            ]);

        return Inertia::render('admin/reports/Index', [
            'reports' => $reports,
        ]);
    }

    /**
     * Tindakan: hide review (set rejected) dan tandai laporan sebagai resolved_hide.
     */
    public function hide(ReviewReport $report): RedirectResponse
    {
        if ($report->status !== ReviewReport::STATUS_PENDING) {
            return back()->with('flash.error', 'Laporan sudah diselesaikan sebelumnya.');
        }

        $review = $report->review;
        if ($review && $review->status !== Review::STATUS_REJECTED) {
            $review->update(['status' => Review::STATUS_REJECTED]);
            ReviewAuditLog::create([
                'user_id' => $review->user_id,
                'movie_id' => $review->movie_id,
                'review_id' => $review->id,
                'rule_triggered' => 'admin_hide_via_report',
                'action' => ReviewAuditLog::ACTION_REJECTED,
                'reason' => "Disembunyikan via laporan #{$report->id}: ".auth()->user()->name,
                'payload_excerpt' => mb_substr($review->body, 0, 200),
                'ip' => request()->ip(),
            ]);
        }

        $report->update(['status' => ReviewReport::STATUS_RESOLVED_HIDE]);

        return back()->with('flash.success', 'Ulasan disembunyikan dan laporan ditutup.');
    }

    /**
     * Tindakan: keep review (tidak tindakan), laporan ditolak.
     */
    public function dismiss(ReviewReport $report): RedirectResponse
    {
        if ($report->status !== ReviewReport::STATUS_PENDING) {
            return back()->with('flash.error', 'Laporan sudah diselesaikan sebelumnya.');
        }

        $report->update(['status' => ReviewReport::STATUS_RESOLVED_KEEP]);

        return back()->with('flash.success', 'Laporan ditolak — ulasan tetap dipertahankan.');
    }
}
