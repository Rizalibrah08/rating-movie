<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyReviewsController extends Controller
{
    /**
     * Halaman ulasan milik user (semua status: published/pending/rejected).
     */
    public function index(Request $request): Response
    {
        $reviews = $request->user()
            ->reviews()
            ->with(['movie:id,title,slug,poster_path,poster_url,year'])
            ->latest()
            ->paginate(15)
            ->through(fn (Review $r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'body' => $r->body,
                'status' => $r->status,
                'created_at' => $r->created_at?->toISOString(),
                'movie' => $r->movie ? [
                    'id' => $r->movie->id,
                    'title' => $r->movie->title,
                    'slug' => $r->movie->slug,
                    'year' => $r->movie->year,
                    'poster' => $r->movie->poster,
                ] : null,
            ]);

        return Inertia::render('profile/MyReviews', [
            'reviews' => $reviews,
        ]);
    }

    /**
     * Soft-delete review milik user. Policy: user hanya boleh hapus ulasan miliknya.
     */
    public function destroy(Review $review): RedirectResponse
    {
        if ($review->user_id !== request()->user()->id) {
            abort(403, 'Kamu hanya bisa menghapus ulasan milikmu sendiri.');
        }

        $review->delete();

        return back()->with('flash.success', 'Ulasan dihapus.');
    }
}
