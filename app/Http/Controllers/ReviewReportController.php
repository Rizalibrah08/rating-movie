<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewReportRequest;
use App\Models\Review;
use App\Models\ReviewReport;
use Illuminate\Http\RedirectResponse;

class ReviewReportController extends Controller
{
    /**
     * Member submit laporan untuk satu ulasan.
     * Cegah double-report user yang sama untuk review yang sama (unique constraint).
     */
    public function store(StoreReviewReportRequest $request, Review $review): RedirectResponse
    {
        $userId = $request->user()->id;

        // Cegah report ulasan sendiri
        if ($review->user_id === $userId) {
            return back()->with('flash.error', 'Kamu tidak dapat melaporkan ulasanmu sendiri.');
        }

        $existing = ReviewReport::where('review_id', $review->id)
            ->where('reporter_id', $userId)
            ->first();
        if ($existing) {
            return back()->with('flash.error', 'Kamu sudah pernah melaporkan ulasan ini.');
        }

        ReviewReport::create([
            'review_id' => $review->id,
            'reporter_id' => $userId,
            'reason' => $request->validated('reason'),
            'note' => $request->validated('note'),
            'status' => ReviewReport::STATUS_PENDING,
        ]);

        return back()->with('flash.success', 'Laporan telah dikirim. Tim moderasi akan meninjau ulasan ini.');
    }
}
