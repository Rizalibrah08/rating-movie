<?php

use App\Models\Movie;
use App\Models\Review;
use App\Models\ReviewAuditLog;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'email_verified_at' => now(),
    ]);
    $this->user = User::factory()->create([
        'role' => User::ROLE_USER,
        'email_verified_at' => now(),
    ]);
});

it('blocks non-admin from moderation queue', function () {
    $this->actingAs($this->user)->get('/admin/moderation')->assertForbidden();
});

it('lists pending reviews for admin', function () {
    Review::factory(3)->pending()->create();
    Review::factory(2)->published()->create();
    Review::factory(1)->rejected()->create();

    $this->actingAs($this->admin)
        ->get('/admin/moderation')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('admin/moderation/Index')
            ->has('reviews.data', 3)
        );
});

it('approves a pending review and writes audit log', function () {
    $review = Review::factory()->pending()->create();

    $this->actingAs($this->admin)
        ->post("/admin/moderation/{$review->id}/approve")
        ->assertRedirect();

    $review->refresh();
    expect($review->status)->toBe(Review::STATUS_PUBLISHED);

    $log = ReviewAuditLog::where('review_id', $review->id)
        ->where('rule_triggered', 'admin_approve')
        ->first();
    expect($log)->not->toBeNull()
        ->and($log->action)->toBe(ReviewAuditLog::ACTION_PUBLISHED);
});

it('rejects a pending review and writes audit log', function () {
    $review = Review::factory()->pending()->create();

    $this->actingAs($this->admin)
        ->post("/admin/moderation/{$review->id}/reject")
        ->assertRedirect();

    $review->refresh();
    expect($review->status)->toBe(Review::STATUS_REJECTED);

    $log = ReviewAuditLog::where('review_id', $review->id)
        ->where('rule_triggered', 'admin_reject')
        ->first();
    expect($log)->not->toBeNull()
        ->and($log->action)->toBe(ReviewAuditLog::ACTION_REJECTED);
});

it('cannot approve a non-pending review', function () {
    $review = Review::factory()->published()->create();

    $this->actingAs($this->admin)
        ->post("/admin/moderation/{$review->id}/approve")
        ->assertSessionHas('flash.error');

    expect($review->fresh()->status)->toBe(Review::STATUS_PUBLISHED);
});

it('shows rule_triggered from audit log on the queue', function () {
    $review = Review::factory()->pending()->create();
    ReviewAuditLog::create([
        'user_id' => $review->user_id,
        'movie_id' => $review->movie_id,
        'review_id' => $review->id,
        'rule_triggered' => 'hourly_quota',
        'action' => ReviewAuditLog::ACTION_PENDING,
        'reason' => 'Aktivitas tinggi terdeteksi.',
        'payload_excerpt' => mb_substr($review->body, 0, 200),
        'ip' => '127.0.0.1',
    ]);

    $this->actingAs($this->admin)
        ->get('/admin/moderation')
        ->assertInertia(fn ($p) => $p
            ->where('reviews.data.0.rule_triggered', 'hourly_quota')
            ->where('reviews.data.0.reason', 'Aktivitas tinggi terdeteksi.')
        );
});
