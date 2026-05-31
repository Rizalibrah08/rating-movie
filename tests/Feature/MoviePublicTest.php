<?php

use App\Models\Genre;
use App\Models\Movie;
use App\Models\Review;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders the public movies index page', function () {
    Movie::factory(3)->create();

    $response = $this->get('/movies');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('movies/Index')
        ->has('movies.data', 3)
        ->has('genres')
    );
});

it('filters movies by genre slug', function () {
    $action = Genre::factory()->create(['name' => 'Aksi', 'slug' => 'aksi']);
    $drama = Genre::factory()->create(['name' => 'Drama', 'slug' => 'drama']);

    $aksiMovie = Movie::factory()->create(['title' => 'Pengejaran']);
    $aksiMovie->genres()->attach($action->id);

    $dramaMovie = Movie::factory()->create(['title' => 'Cinta Senja']);
    $dramaMovie->genres()->attach($drama->id);

    $response = $this->get('/movies?genre=aksi');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('movies.data', 1)
        ->where('movies.data.0.title', 'Pengejaran')
    );
});

it('filters movies by year', function () {
    Movie::factory()->create(['title' => 'Film 2020', 'year' => 2020]);
    Movie::factory()->create(['title' => 'Film 2024', 'year' => 2024]);

    $response = $this->get('/movies?year=2020');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('movies.data', 1)
        ->where('movies.data.0.title', 'Film 2020')
    );
});

it('searches movies by title query', function () {
    Movie::factory()->create(['title' => 'Pengejaran Senja']);
    Movie::factory()->create(['title' => 'Reruntuhan Kota']);

    $response = $this->get('/movies?q=senja');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('movies.data', 1)
        ->where('movies.data.0.title', 'Pengejaran Senja')
    );
});

it('renders movie detail with published reviews only', function () {
    $movie = Movie::factory()->create(['slug' => 'film-uji']);
    $user = User::factory()->create();

    Review::factory()->published()->create([
        'movie_id' => $movie->id,
        'user_id' => $user->id,
        'rating' => 90,
    ]);
    Review::factory()->pending()->create([
        'movie_id' => $movie->id,
        'user_id' => User::factory()->create()->id,
        'rating' => 30,
    ]);
    Review::factory()->rejected()->create([
        'movie_id' => $movie->id,
        'user_id' => User::factory()->create()->id,
    ]);

    $response = $this->get('/movies/film-uji');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('movies/Show')
        ->where('movie.slug', 'film-uji')
        ->where('movie.review_count', 1)
        ->where('movie.avg_score', 90)
        ->has('reviews.data', 1)
        ->where('reviews.data.0.rating', 90)
    );
});

it('returns 404 for non-existent movie slug', function () {
    $response = $this->get('/movies/this-does-not-exist');

    $response->assertNotFound();
});

it('computes average score correctly when there are multiple reviews', function () {
    $movie = Movie::factory()->create(['slug' => 'multi-review']);

    Review::factory()->published()->create(['movie_id' => $movie->id, 'rating' => 80, 'user_id' => User::factory()]);
    Review::factory()->published()->create(['movie_id' => $movie->id, 'rating' => 60, 'user_id' => User::factory()]);
    Review::factory()->published()->create(['movie_id' => $movie->id, 'rating' => 40, 'user_id' => User::factory()]);

    $response = $this->get('/movies/multi-review');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('movie.review_count', 3)
        ->where('movie.avg_score', 60) // (80+60+40)/3 = 60
    );
});
