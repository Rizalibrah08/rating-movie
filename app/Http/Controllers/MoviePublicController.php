<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Movie;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MoviePublicController extends Controller
{
    /**
     * Movies listing with search/filter/sort.
     */
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'genre' => ['nullable', 'string', 'max:80'],
            'year' => ['nullable', 'integer', 'between:1900,2100'],
            'sort' => ['nullable', 'in:newest,score,reviews'],
        ]);

        $sort = $validated['sort'] ?? 'newest';

        $movies = Movie::query()
            ->with(['genres:id,name,slug'])
            ->withCount(['reviews as review_count' => fn ($q) => $q->where('status', Review::STATUS_PUBLISHED)])
            ->withAvg(['reviews as avg_score' => fn ($q) => $q->where('status', Review::STATUS_PUBLISHED)], 'rating')
            ->when($validated['q'] ?? null, fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->when($validated['genre'] ?? null, fn ($q, $slug) => $q->whereHas('genres', fn ($qq) => $qq->where('slug', $slug)))
            ->when($validated['year'] ?? null, fn ($q, $year) => $q->where('year', $year))
            ->when($sort === 'newest', fn ($q) => $q->orderByDesc('year')->orderByDesc('id'))
            ->when($sort === 'score', fn ($q) => $q->orderByDesc('avg_score')->orderByDesc('review_count'))
            ->when($sort === 'reviews', fn ($q) => $q->orderByDesc('review_count'))
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Movie $m) => [
                'id' => $m->id,
                'title' => $m->title,
                'slug' => $m->slug,
                'year' => $m->year,
                'duration_min' => $m->duration_min,
                'poster' => $m->poster,
                'avg_score' => $m->avg_score !== null ? (int) round($m->avg_score) : null,
                'review_count' => $m->review_count,
                'genres' => $m->genres->map(fn ($g) => ['id' => $g->id, 'name' => $g->name, 'slug' => $g->slug]),
            ]);

        return Inertia::render('movies/Index', [
            'movies' => $movies,
            'filters' => [
                'q' => $validated['q'] ?? '',
                'genre' => $validated['genre'] ?? '',
                'year' => $validated['year'] ?? null,
                'sort' => $sort,
            ],
            'genres' => Genre::query()->orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    /**
     * Movie detail with published reviews.
     */
    public function show(Movie $movie, \Illuminate\Http\Request $request): Response
    {
        $movie->load(['genres:id,name,slug']);

        $user = $request->user();
        $userReviewStatus = null;
        if ($user) {
            $existing = $movie->reviews()
                ->where('user_id', $user->id)
                ->whereIn('status', [Review::STATUS_PUBLISHED, Review::STATUS_PENDING])
                ->first(['id', 'status', 'rating']);
            if ($existing) {
                $userReviewStatus = [
                    'id' => $existing->id,
                    'status' => $existing->status,
                    'rating' => $existing->rating,
                ];
            }
        }

        $reviewsQuery = $movie->reviews()
            ->where('status', Review::STATUS_PUBLISHED)
            ->with(['user:id,name'])
            ->latest();

        $stats = DB::table('reviews')
            ->where('movie_id', $movie->id)
            ->where('status', Review::STATUS_PUBLISHED)
            ->selectRaw('AVG(rating) as avg_score, COUNT(*) as review_count')
            ->first();

        return Inertia::render('movies/Show', [
            'movie' => [
                'id' => $movie->id,
                'title' => $movie->title,
                'slug' => $movie->slug,
                'synopsis' => $movie->synopsis,
                'year' => $movie->year,
                'duration_min' => $movie->duration_min,
                'director' => $movie->director,
                'poster' => $movie->poster,
                'backdrop' => $movie->backdrop,
                'has_backdrop' => $movie->has_backdrop,
                'genres' => $movie->genres->map(fn ($g) => ['id' => $g->id, 'name' => $g->name, 'slug' => $g->slug]),
                'avg_score' => $stats && $stats->avg_score !== null ? (int) round((float) $stats->avg_score) : null,
                'review_count' => (int) ($stats->review_count ?? 0),
            ],
            'reviews' => $reviewsQuery->paginate(10)
                ->through(fn (Review $r) => [
                    'id' => $r->id,
                    'rating' => $r->rating,
                    'body' => $r->body,
                    'created_at' => $r->created_at?->toISOString(),
                    'user' => [
                        'id' => $r->user->id,
                        'name' => $r->user->name,
                    ],
                ]),
            // Client-side hint untuk ReviewForm filter feedback
            'blockedKeywords' => \App\Models\BlockedKeyword::activeList(),
            'userReviewStatus' => $userReviewStatus,
        ]);
    }
}
