<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Review;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        // Hero rotator — hanya film yang punya backdrop
        $heroMovies = Movie::query()
            ->where(fn ($q) => $q->whereNotNull('backdrop_path')->orWhereNotNull('backdrop_url'))
            ->withCount(['reviews as review_count' => fn ($q) => $q->where('status', Review::STATUS_PUBLISHED)])
            ->withAvg(['reviews as avg_score' => fn ($q) => $q->where('status', Review::STATUS_PUBLISHED)], 'rating')
            ->orderByDesc('avg_score')
            ->orderByDesc('review_count')
            ->limit(5)
            ->get()
            ->map(fn (Movie $m) => [
                'id' => $m->id,
                'title' => $m->title,
                'slug' => $m->slug,
                'synopsis' => $m->synopsis,
                'year' => $m->year,
                'duration_min' => $m->duration_min,
                'poster' => $m->poster,
                'backdrop' => $m->backdrop,
                'avg_score' => $m->avg_score !== null ? (int) round((float) $m->avg_score) : null,
                'review_count' => (int) $m->review_count,
            ]);

        $popularThisWeek = Movie::query()
            ->whereHas('reviews', fn ($q) => $q
                ->where('status', Review::STATUS_PUBLISHED)
                ->where('created_at', '>=', now()->subWeek())
            )
            ->withCount(['reviews as recent_review_count' => fn ($q) => $q
                ->where('status', Review::STATUS_PUBLISHED)
                ->where('created_at', '>=', now()->subWeek()),
            ])
            ->withAvg(['reviews as avg_score' => fn ($q) => $q->where('status', Review::STATUS_PUBLISHED)], 'rating')
            ->orderByDesc('recent_review_count')
            ->limit(8)
            ->get()
            ->map(fn (Movie $m) => [
                'id' => $m->id,
                'title' => $m->title,
                'slug' => $m->slug,
                'year' => $m->year,
                'duration_min' => $m->duration_min,
                'poster' => $m->poster,
                'avg_score' => $m->avg_score !== null ? (int) round((float) $m->avg_score) : null,
                'review_count' => (int) $m->recent_review_count,
                'genres' => [],
            ]);

        $recentReviews = Review::query()
            ->where('status', Review::STATUS_PUBLISHED)
            ->with(['user:id,name', 'movie:id,title,slug,poster_path,poster_url'])
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(fn (Review $r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'body' => $r->body,
                'created_at' => $r->created_at?->toISOString(),
                'user' => ['id' => $r->user->id, 'name' => $r->user->name],
                'movie' => [
                    'id' => $r->movie->id,
                    'title' => $r->movie->title,
                    'slug' => $r->movie->slug,
                    'poster' => $r->movie->poster,
                ],
            ]);

        return Inertia::render('home/Index', [
            'heroMovies' => $heroMovies,
            'popularThisWeek' => $popularThisWeek,
            'recentReviews' => $recentReviews,
        ]);
    }
}
