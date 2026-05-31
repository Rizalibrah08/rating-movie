<?php

use App\Models\BlockedKeyword;
use App\Models\Movie;
use App\Models\Review;
use App\Models\ReviewAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    $this->user = User::factory()->create([
        'role' => User::ROLE_USER,
        'email_verified_at' => now(),
    ]);
    $this->movie = Movie::factory()->create();
});

it('requires authentication to submit review', function () {
    $this->post('/reviews', [
        'movie_id' => $this->movie->id,
        'rating' => 80,
        'body' => 'Film yang sangat menarik dan layak untuk ditonton kembali.',
    ])->assertRedirect('/login');
});

it('publishes a clean review', function () {
    $body = 'Film ini sangat luar biasa dari awal sampai akhir, akting natural sekali.';

    $response = $this->actingAs($this->user)->post('/reviews', [
        'movie_id' => $this->movie->id,
        'rating' => 90,
        'body' => $body,
    ]);

    $response->assertRedirect("/movies/{$this->movie->slug}");

    $review = Review::firstWhere('movie_id', $this->movie->id);
    expect($review)->not->toBeNull()
        ->and($review->status)->toBe(Review::STATUS_PUBLISHED)
        ->and($review->rating)->toBe(90);

    $log = ReviewAuditLog::firstWhere('review_id', $review->id);
    expect($log)->not->toBeNull()
        ->and($log->action)->toBe(ReviewAuditLog::ACTION_PUBLISHED);
});

it('rejects review with blacklisted keyword', function () {
    BlockedKeyword::factory()->create(['keyword' => 'menjijikkan', 'is_active' => true]);

    $response = $this->actingAs($this->user)->post('/reviews', [
        'movie_id' => $this->movie->id,
        'rating' => 30,
        'body' => 'Film ini benar-benar menjijikkan dan sangat tidak layak ditonton.',
    ]);

    $response->assertSessionHasErrors('body');

    expect(Review::count())->toBe(0);

    $log = ReviewAuditLog::first();
    expect($log)->not->toBeNull()
        ->and($log->action)->toBe(ReviewAuditLog::ACTION_REJECTED)
        ->and($log->rule_triggered)->toBe('blacklist_keyword');
});

it('rejects review that is too short', function () {
    $response = $this->actingAs($this->user)->post('/reviews', [
        'movie_id' => $this->movie->id,
        'rating' => 50,
        'body' => 'Film ini cukup oke saja.', // < 30 char OR < 5 words
    ]);

    $response->assertSessionHasErrors('body');
    expect(Review::count())->toBe(0);

    $log = ReviewAuditLog::first();
    expect($log->rule_triggered)->toBe('length');
});

it('rejects review containing url', function () {
    $response = $this->actingAs($this->user)->post('/reviews', [
        'movie_id' => $this->movie->id,
        'rating' => 70,
        'body' => 'Coba kunjungi https://situsspam.com untuk download lebih bagus dari ini.',
    ]);

    $response->assertSessionHasErrors('body');
    $log = ReviewAuditLog::first();
    expect($log->rule_triggered)->toBe('url_detection');
});

it('flags review as pending when hourly quota exceeded', function () {
    // 5 ulasan dalam 1 jam terakhir untuk user yang sama (di film berbeda)
    Review::factory(5)->create([
        'user_id' => $this->user->id,
        'created_at' => now()->subMinutes(20),
    ]);

    $response = $this->actingAs($this->user)->post('/reviews', [
        'movie_id' => $this->movie->id,
        'rating' => 75,
        'body' => 'Film ini bagus sekali, layak ditonton bersama keluarga di akhir pekan.',
    ]);

    $response->assertRedirect("/movies/{$this->movie->slug}");

    $review = Review::firstWhere('movie_id', $this->movie->id);
    expect($review->status)->toBe(Review::STATUS_PENDING);

    $log = ReviewAuditLog::firstWhere('review_id', $review->id);
    expect($log->action)->toBe(ReviewAuditLog::ACTION_PENDING)
        ->and($log->rule_triggered)->toBe('hourly_quota');
});

it('rejects when user already reviewed the same movie', function () {
    Review::factory()->published()->create([
        'user_id' => $this->user->id,
        'movie_id' => $this->movie->id,
    ]);

    $response = $this->actingAs($this->user)->post('/reviews', [
        'movie_id' => $this->movie->id,
        'rating' => 80,
        'body' => 'Mau review ulang film ini sekali lagi karena sangat keren menurut saya.',
    ]);

    $response->assertSessionHasErrors('body');
    expect(Review::count())->toBe(1); // hanya yang lama, tidak nambah
});

it('validates rating range 0-100', function () {
    $response = $this->actingAs($this->user)->post('/reviews', [
        'movie_id' => $this->movie->id,
        'rating' => 150,
        'body' => 'Body teks valid yang cukup panjang untuk memenuhi length rule disini.',
    ]);

    $response->assertSessionHasErrors('rating');
});

it('rejects review when cooldown not satisfied', function () {
    // Last review 30 detik lalu (cooldown 60s belum terpenuhi)
    Review::factory()->create([
        'user_id' => $this->user->id,
        'movie_id' => Movie::factory(),
        'created_at' => now()->subSeconds(30),
    ]);

    $response = $this->actingAs($this->user)->post('/reviews', [
        'movie_id' => $this->movie->id,
        'rating' => 70,
        'body' => 'Film yang menarik dan menghibur dengan plot yang kompleks dan tak terduga.',
    ]);

    $response->assertSessionHasErrors('body');
    $log = ReviewAuditLog::latest()->first();
    expect($log->rule_triggered)->toBe('cooldown');
});
