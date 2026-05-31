<?php

use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->reviewer = User::factory()->create(['email_verified_at' => now()]);
    $this->reporter = User::factory()->create(['email_verified_at' => now()]);
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'email_verified_at' => now()]);
    $this->review = Review::factory()->published()->create(['user_id' => $this->reviewer->id]);
});

it('member can submit report on someone elses review', function () {
    $this->actingAs($this->reporter)->post("/reviews/{$this->review->id}/report", [
        'reason' => 'spam',
        'note' => 'jelas-jelas spam.',
    ])->assertRedirect();

    expect(ReviewReport::where('review_id', $this->review->id)->where('reporter_id', $this->reporter->id)->exists())->toBeTrue();
});

it('member cannot report own review', function () {
    $this->actingAs($this->reviewer)->post("/reviews/{$this->review->id}/report", [
        'reason' => 'spam',
    ])->assertSessionHas('flash.error');

    expect(ReviewReport::count())->toBe(0);
});

it('cannot double-report the same review', function () {
    ReviewReport::factory()->create([
        'review_id' => $this->review->id,
        'reporter_id' => $this->reporter->id,
    ]);

    $this->actingAs($this->reporter)->post("/reviews/{$this->review->id}/report", [
        'reason' => 'offensive',
    ])->assertSessionHas('flash.error');

    expect(ReviewReport::count())->toBe(1);
});

it('rejects invalid reason', function () {
    $this->actingAs($this->reporter)->post("/reviews/{$this->review->id}/report", [
        'reason' => 'invalid_reason',
    ])->assertSessionHasErrors('reason');
});

it('blocks non-admin from reports queue', function () {
    $this->actingAs($this->reporter)->get('/admin/reports')->assertForbidden();
});

it('lists reports for admin', function () {
    ReviewReport::factory(3)->create(['review_id' => $this->review->id]);

    $this->actingAs($this->admin)
        ->get('/admin/reports')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('admin/reports/Index')
            ->has('reports.data', 3)
        );
});

it('admin hide review action sets review to rejected and report to resolved_hide', function () {
    $report = ReviewReport::factory()->create([
        'review_id' => $this->review->id,
        'reporter_id' => $this->reporter->id,
    ]);

    $this->actingAs($this->admin)
        ->post("/admin/reports/{$report->id}/hide")
        ->assertRedirect();

    $report->refresh();
    expect($report->status)->toBe(ReviewReport::STATUS_RESOLVED_HIDE)
        ->and($this->review->fresh()->status)->toBe(Review::STATUS_REJECTED);
});

it('admin dismiss action sets report to resolved_keep without affecting review', function () {
    $report = ReviewReport::factory()->create([
        'review_id' => $this->review->id,
        'reporter_id' => $this->reporter->id,
    ]);

    $this->actingAs($this->admin)
        ->post("/admin/reports/{$report->id}/dismiss")
        ->assertRedirect();

    $report->refresh();
    expect($report->status)->toBe(ReviewReport::STATUS_RESOLVED_KEEP)
        ->and($this->review->fresh()->status)->toBe(Review::STATUS_PUBLISHED);
});
