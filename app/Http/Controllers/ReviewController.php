<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Movie;
use App\Models\Review;
use App\Models\ReviewAuditLog;
use App\Services\ReviewFilter\Pipeline;
use App\Services\ReviewFilter\ReviewContext;
use App\Services\ReviewFilter\RuleResult;
use App\Services\ReviewFilter\TextNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function __construct(private readonly Pipeline $pipeline) {}

    /**
     * Submit ulasan baru. Tiga jalur output:
     * - reject (422 + back with errors)
     * - flag (status=pending, redirect with notice)
     * - pass (status=published, redirect with success)
     *
     * Setiap keputusan ditulis ke review_audit_logs untuk traceability.
     */
    public function store(StoreReviewRequest $request): RedirectResponse
    {
        $user = $request->user();
        $movie = Movie::findOrFail($request->validated('movie_id'));
        $body = (string) $request->validated('body');
        $rating = (int) $request->validated('rating');
        $ip = $request->ip();

        $ctx = new ReviewContext(
            userId: $user->id,
            movieId: $movie->id,
            rating: $rating,
            body: $body,
            normalizedBody: TextNormalizer::normalize($body),
            bodyHash: TextNormalizer::canonicalHash($body),
            ip: $ip,
            trustScore: (int) $user->trust_score,
        );

        $result = $this->pipeline->run($ctx);

        // === Reject path ===
        if (! $result->passed && $result->severity === RuleResult::SEVERITY_REJECT) {
            $this->logDecision(
                user: $user,
                movie: $movie,
                review: null,
                result: $result,
                action: ReviewAuditLog::ACTION_REJECTED,
                bodyExcerpt: $body,
                ip: $ip,
            );

            return back()
                ->withInput()
                ->withErrors(['body' => $result->reason])
                ->with('flash.error', $result->reason);
        }

        // === Pass / Flag path: simpan review ===
        $action = $result->severity === RuleResult::SEVERITY_FLAG
            ? Review::STATUS_PENDING
            : Review::STATUS_PUBLISHED;

        $review = DB::transaction(function () use ($user, $movie, $rating, $body, $action, $ip) {
            return Review::create([
                'user_id' => $user->id,
                'movie_id' => $movie->id,
                'rating' => $rating,
                'body' => $body,
                'status' => $action,
                'ip' => $ip,
            ]);
        });

        $this->logDecision(
            user: $user,
            movie: $movie,
            review: $review,
            result: $result,
            action: $action === Review::STATUS_PENDING
                ? ReviewAuditLog::ACTION_PENDING
                : ReviewAuditLog::ACTION_PUBLISHED,
            bodyExcerpt: $body,
            ip: $ip,
        );

        $message = match ($action) {
            Review::STATUS_PENDING => $result->reason ?? 'Ulasan kamu sedang ditinjau admin.',
            default => 'Ulasan kamu berhasil dipublikasikan.',
        };

        return redirect()
            ->route('movies.show', $movie->slug)
            ->with('flash.success', $message);
    }

    private function logDecision(
        $user,
        Movie $movie,
        ?Review $review,
        RuleResult $result,
        string $action,
        string $bodyExcerpt,
        ?string $ip,
    ): void {
        ReviewAuditLog::create([
            'user_id' => $user?->id,
            'movie_id' => $movie->id,
            'review_id' => $review?->id,
            'rule_triggered' => $result->rule,
            'action' => $action,
            'reason' => $result->reason,
            'payload_excerpt' => mb_substr($bodyExcerpt, 0, 200),
            'ip' => $ip,
        ]);
    }
}
