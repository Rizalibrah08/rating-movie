<?php

use App\Models\BlockedKeyword;
use App\Models\Movie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->user = User::factory()->create(['role' => 'user']);
    $this->movie = Movie::factory()->create();

    // Default blocked keyword
    BlockedKeyword::factory()->create(['keyword' => 'jelek', 'is_active' => true]);
    BlockedKeyword::factory()->create(['keyword' => 'sampah', 'is_active' => true]);
});

it('rejects leetspeak variant (j3l3k)', function () {
    $response = $this->actingAs($this->user)->post("/reviews", [
        'movie_id' => $this->movie->id,
        'rating' => 80,
        'body' => 'Film ini j3l3k banget.'
    ]);
    $response->assertSessionHasErrors(['body']);
});

it('rejects separated chars variant (j e l e k)', function () {
    $response = $this->actingAs($this->user)->post("/reviews", [
        'movie_id' => $this->movie->id,
        'rating' => 80,
        'body' => 'Film ini j e l e k banget.'
    ]);
    $response->assertSessionHasErrors(['body']);
});

it('rejects fuzzy match typo (sampaj)', function () {
    $response = $this->actingAs($this->user)->post("/reviews", [
        'movie_id' => $this->movie->id,
        'rating' => 80,
        'body' => 'Film ini sampaj banget.'
    ]);
    $response->assertSessionHasErrors(['body']);
});

it('rejects url detection', function () {
    $response = $this->actingAs($this->user)->post("/reviews", [
        'movie_id' => $this->movie->id,
        'rating' => 80,
        'body' => 'Kunjungi http://spam.com'
    ]);
    $response->assertSessionHasErrors(['body']);
});

it('allows whitelisted phrase to bypass filter', function () {
    BlockedKeyword::factory()->create(['keyword' => 'gila', 'is_active' => true]);
    BlockedKeyword::factory()->create(['keyword' => 'ide gila', 'category' => 'whitelist', 'is_active' => true]);

    $response = $this->actingAs($this->user)->post("/reviews", [
        'movie_id' => $this->movie->id,
        'rating' => 80,
        'body' => 'Saya merasa ada banyak ide gila dari sutradara dalam film ini.'
    ]);
    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
});

it('bypasses URL block if user has high trust score', function () {
    $trustedUser = User::factory()->create(['role' => 'user', 'trust_score' => 100]);

    $response = $this->actingAs($trustedUser)->post("/reviews", [
        'movie_id' => $this->movie->id,
        'rating' => 80,
        'body' => 'Menurut pendapat saya, anda bisa baca selengkapnya di http://aman.com ya.'
    ]);
    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
});

it('rejects using regex keyword pattern', function () {
    BlockedKeyword::factory()->create(['keyword' => 'b[a@]bi', 'is_regex' => true, 'is_active' => true]);

    $response = $this->actingAs($this->user)->post("/reviews", [
        'movie_id' => $this->movie->id,
        'rating' => 80,
        'body' => 'Film ini sangat buruk, dasar b@bi banget actingnya.'
    ]);
    $response->assertSessionHasErrors(['body']);
});

it('allows admin to update trust score', function () {
    $response = $this->actingAs($this->admin)->patch("/admin/users/{$this->user->id}/trust-score", [
        'trust_score' => 75
    ]);
    $response->assertRedirect();
    expect($this->user->fresh()->trust_score)->toBe(75);
});
