<?php

use App\Models\BlockedKeyword;
use App\Models\Movie;
use App\Models\Review;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('completes happy path: register → submit review → admin approve → tampil', function () {
    // 1. Setup: seed admin, blocked keyword, movie
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'email_verified_at' => now(),
    ]);
    BlockedKeyword::factory()->create(['keyword' => 'mubazir', 'is_active' => true]);
    $movie = Movie::factory()->create(['slug' => 'film-uji-e2e']);

    // 2. Register member baru via Fortify
    $this->post('/register', [
        'name' => 'Member Baru',
        'email' => 'newmember@example.com',
        'password' => 'StrongP4ss!',
        'password_confirmation' => 'StrongP4ss!',
    ])->assertRedirect();

    $member = User::firstWhere('email', 'newmember@example.com');
    expect($member)->not->toBeNull();
    $member->update(['email_verified_at' => now()]);

    // 3. Submit ulasan valid (publish langsung)
    $this->actingAs($member)->post('/reviews', [
        'movie_id' => $movie->id,
        'rating' => 85,
        'body' => 'Film yang luar biasa dari awal sampai akhir, sangat layak ditonton ulang.',
    ])->assertRedirect("/movies/{$movie->slug}");

    expect(Review::where('movie_id', $movie->id)->where('status', 'published')->count())->toBe(1);

    // 4. Verifikasi tampil di halaman detail
    $this->get("/movies/{$movie->slug}")
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->where('movie.review_count', 1)
            ->where('movie.avg_score', 85)
        );

    // 5. Submit ulasan kena flag (5 review dalam 1 jam → pending) untuk member yang sama
    $movieB = Movie::factory()->create(['slug' => 'film-pending']);

    // Bypass cooldown 60s: majukan first review's created_at ke 10 menit lalu
    Review::where('user_id', $member->id)->update(['created_at' => now()->subMinutes(10)]);

    // Simulate 5 reviews already in last hour (lewati cooldown dengan cara memajukan created_at)
    Review::factory(5)->create([
        'user_id' => $member->id,
        'created_at' => now()->subMinutes(15),
    ]);

    // Submit ulasan ke-6 → harus masuk pending karena hourly quota
    $this->actingAs($member)->post('/reviews', [
        'movie_id' => $movieB->id,
        'rating' => 60,
        'body' => 'Ulasan keenam dalam satu jam, mestinya masuk antrian moderasi admin.',
    ])->assertRedirect();

    $pending = Review::where('movie_id', $movieB->id)->where('status', 'pending')->first();
    expect($pending)->not->toBeNull();

    // 6. Admin approve → langsung published
    $this->actingAs($admin)->post("/admin/moderation/{$pending->id}/approve")->assertRedirect();
    expect($pending->fresh()->status)->toBe('published');

    // 7. Submit ulasan dengan blacklist keyword → ditolak
    $movieC = Movie::factory()->create(['slug' => 'film-rejected']);
    $this->actingAs($member)->post('/reviews', [
        'movie_id' => $movieC->id,
        'rating' => 30,
        'body' => 'Film ini benar-benar mubazir banget tidak layak ditonton sama sekali ya.',
    ])->assertSessionHasErrors('body');

    expect(Review::where('movie_id', $movieC->id)->count())->toBe(0);
});

it('rate-limits review submission per user', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $movie = Movie::factory()->create();

    // 5 successful submits would hit the cooldown rule from the filter pipeline,
    // but the rate limiter middleware runs BEFORE controller — so 6th request returns 429.
    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($user)->post('/reviews', [
            'movie_id' => Movie::factory()->create()->id,
            'rating' => 70,
            'body' => "Ulasan ke-{$i} yang cukup panjang dan layak untuk lulus length rule.",
        ]);
    }

    $response = $this->actingAs($user)->post('/reviews', [
        'movie_id' => $movie->id,
        'rating' => 50,
        'body' => 'Submit ke-6 dalam 1 menit, harus kena rate limit.',
    ]);

    $response->assertStatus(429);
});

it('honeypot field rejects submission with logged warning', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $movie = Movie::factory()->create();

    $this->actingAs($user)->post('/reviews', [
        'movie_id' => $movie->id,
        'rating' => 70,
        'body' => 'Ulasan dengan honeypot terisi (simulasi bot).',
        'website' => 'http://bot-filled.com',
    ])->assertSessionHasErrors('body');

    expect(Review::count())->toBe(0);
});
