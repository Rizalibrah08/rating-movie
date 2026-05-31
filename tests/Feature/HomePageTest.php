<?php

use App\Models\Movie;
use App\Models\Review;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders home with hero movies that have backdrop only', function () {
    // 3 movie dengan backdrop, 2 tanpa
    $withBackdrop = Movie::factory(3)->withBackdrop()->create();
    Movie::factory(2)->withoutBackdrop()->create();

    foreach ($withBackdrop as $m) {
        Review::factory(2)->published()->create(['movie_id' => $m->id]);
    }

    cache()->forget('home:hero');

    $response = $this->get('/');

    $response->assertOk();
    $response->assertInertia(fn ($p) => $p
        ->component('home/Index')
        ->has('heroMovies', 3)  // hanya yang punya backdrop
        ->has('popularThisWeek')
        ->has('recentReviews')
    );
});

it('renders home empty state when no movies have backdrop', function () {
    Movie::factory(2)->withoutBackdrop()->create();
    cache()->forget('home:hero');

    $response = $this->get('/');

    $response->assertOk();
    $response->assertInertia(fn ($p) => $p
        ->component('home/Index')
        ->has('heroMovies', 0)
    );
});

it('shows recently reviewed activity feed', function () {
    $movie = Movie::factory()->create();
    Review::factory(3)->published()->create(['movie_id' => $movie->id]);

    cache()->forget('home:hero');

    $this->get('/')->assertInertia(fn ($p) => $p
        ->has('recentReviews', 3)
    );
});
