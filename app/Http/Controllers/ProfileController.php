<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Public profile: stats user + grid ulasan published mereka.
     * URL: /u/{user:id}
     */
    public function show(User $user): Response
    {
        $publishedReviews = $user->reviews()
            ->where('status', Review::STATUS_PUBLISHED)
            ->with(['movie:id,title,slug,poster_path,poster_url,year'])
            ->latest()
            ->paginate(12)
            ->through(fn (Review $r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'body' => $r->body,
                'created_at' => $r->created_at?->toISOString(),
                'movie' => $r->movie ? [
                    'id' => $r->movie->id,
                    'title' => $r->movie->title,
                    'slug' => $r->movie->slug,
                    'year' => $r->movie->year,
                    'poster' => $r->movie->poster,
                ] : null,
            ]);

        $totalReviews = $user->reviews()->where('status', Review::STATUS_PUBLISHED)->count();
        $avgGiven = $user->reviews()->where('status', Review::STATUS_PUBLISHED)->avg('rating');

        return Inertia::render('profile/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'member_since' => $user->created_at?->toISOString(),
            ],
            'reviews' => $publishedReviews,
            'stats' => [
                'total_reviews' => $totalReviews,
                'avg_given' => $avgGiven !== null ? (int) round((float) $avgGiven) : null,
            ],
        ]);
    }
}
