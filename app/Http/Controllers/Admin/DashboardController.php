<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\Review;
use App\Models\ReviewAuditLog;
use App\Models\ReviewReport;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $stats = Cache::remember('admin:dashboard', 300, function () {
            // Counts
            $totalMovies = Movie::count();
            $totalUsers = User::where('role', User::ROLE_USER)->count();
            $totalAdmins = User::where('role', User::ROLE_ADMIN)->count();

            $reviewCounts = Review::query()
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $totalReports = ReviewReport::count();
            $pendingReports = ReviewReport::where('status', ReviewReport::STATUS_PENDING)->count();

            // Avg score (only published)
            $avgScore = Review::where('status', Review::STATUS_PUBLISHED)->avg('rating');
            $avgScore = $avgScore !== null ? (int) round((float) $avgScore) : null;

            // Color distribution histogram (published only)
            $colorDistribution = [
                'green' => Review::where('status', Review::STATUS_PUBLISHED)->where('rating', '>=', 75)->count(),
                'yellow' => Review::where('status', Review::STATUS_PUBLISHED)->whereBetween('rating', [50, 74])->count(),
                'red' => Review::where('status', Review::STATUS_PUBLISHED)->where('rating', '<', 50)->count(),
            ];

            // Top 5 movies by avg score (min 2 reviews)
            $topMovies = Movie::query()
                ->withAvg(['reviews as avg_score' => fn ($q) => $q->where('status', Review::STATUS_PUBLISHED)], 'rating')
                ->withCount(['reviews as review_count' => fn ($q) => $q->where('status', Review::STATUS_PUBLISHED)])
                ->having('review_count', '>=', 2)
                ->orderByDesc('avg_score')
                ->limit(5)
                ->get()
                ->map(fn (Movie $m) => [
                    'id' => $m->id,
                    'title' => $m->title,
                    'slug' => $m->slug,
                    'year' => $m->year,
                    'duration_min' => $m->duration_min,
                    'poster' => $m->poster,
                    'avg_score' => $m->avg_score !== null ? (int) round((float) $m->avg_score) : null,
                    'review_count' => (int) $m->review_count,
                    'genres' => [],
                ]);

            // Rule trigger counts (last 30 days)
            $ruleTriggers = ReviewAuditLog::query()
                ->where('created_at', '>=', now()->subDays(30))
                ->whereNotNull('rule_triggered')
                ->select('rule_triggered', DB::raw('COUNT(*) as count'))
                ->groupBy('rule_triggered')
                ->orderByDesc('count')
                ->get()
                ->map(fn ($r) => ['rule' => $r->rule_triggered, 'count' => (int) $r->count]);

            return [
                'totalMovies' => $totalMovies,
                'totalUsers' => $totalUsers,
                'totalAdmins' => $totalAdmins,
                'reviewsByStatus' => [
                    'published' => (int) ($reviewCounts[Review::STATUS_PUBLISHED] ?? 0),
                    'pending' => (int) ($reviewCounts[Review::STATUS_PENDING] ?? 0),
                    'rejected' => (int) ($reviewCounts[Review::STATUS_REJECTED] ?? 0),
                ],
                'totalReports' => $totalReports,
                'pendingReports' => $pendingReports,
                'avgScore' => $avgScore,
                'colorDistribution' => $colorDistribution,
                'topMovies' => $topMovies,
                'ruleTriggers' => $ruleTriggers,
            ];
        });

        return Inertia::render('admin/dashboard/Index', $stats);
    }
}
