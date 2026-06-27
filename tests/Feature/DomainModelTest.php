<?php

use App\Models\Genre;
use App\Models\Movie;
use App\Models\Review;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('can create a genre with auto slug', function () {
    $genre = Genre::factory()->create(['name' => 'Aksi', 'slug' => null]);

    expect($genre->slug)->toBe('aksi');
});

it('can create a movie with required title and poster', function () {
    $movie = Movie::factory()->create([
        'title' => 'Pengejaran Senja',
        'slug' => null,
        'poster_url' => 'https://example.com/p.jpg',
        'poster_path' => null,
    ]);

    expect($movie->title)->toBe('Pengejaran Senja')
        ->and($movie->slug)->toBe('pengejaran-senja')
        ->and($movie->poster)->toBe('https://example.com/p.jpg');
});

it('returns null backdrop when both backdrop fields are empty', function () {
    $movie = Movie::factory()->withoutBackdrop()->create();

    expect($movie->backdrop)->toBeNull()
        ->and($movie->has_backdrop)->toBeFalse();
});

it('returns external backdrop URL when set', function () {
    $movie = Movie::factory()->withBackdrop()->create();

    expect($movie->backdrop)->toStartWith('/image/')
        ->and($movie->has_backdrop)->toBeTrue();
});

it('attaches genres to a movie via pivot', function () {
    $movie = Movie::factory()->create();
    $genres = Genre::factory(3)->create();

    $movie->genres()->attach($genres->pluck('id'));

    expect($movie->fresh()->genres)->toHaveCount(3);
});

it('creates a review with rating in 0..100 range', function () {
    $review = Review::factory()->create(['rating' => 87]);

    expect($review->rating)->toBe(87)
        ->and($review->status)->toBe(Review::STATUS_PUBLISHED);
});

it('rejects rating > 100 at the database level (unsigned tinyint overflow)', function () {
    expect(fn () => Review::factory()->create(['rating' => 256]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('maps rating to score_category green/yellow/red', function () {
    $high = Review::factory()->create(['rating' => 90]);
    $mid = Review::factory()->create(['rating' => 60]);
    $low = Review::factory()->create(['rating' => 20]);

    expect($high->score_category)->toBe('green')
        ->and($mid->score_category)->toBe('yellow')
        ->and($low->score_category)->toBe('red');
});

it('belongs review to user and movie', function () {
    $user = User::factory()->create();
    $movie = Movie::factory()->create();
    $review = Review::factory()->create(['user_id' => $user->id, 'movie_id' => $movie->id]);

    expect($review->user->is($user))->toBeTrue()
        ->and($review->movie->is($movie))->toBeTrue();
});

it('only includes movies with backdrop in hero query', function () {
    Movie::factory(3)->withBackdrop()->create();
    Movie::factory(2)->withoutBackdrop()->create();

    $heroEligible = Movie::query()
        ->where(fn ($q) => $q->whereNotNull('backdrop_path')->orWhereNotNull('backdrop_url'))
        ->count();

    expect($heroEligible)->toBe(3);
});

it('soft-deletes a review and excludes from default queries', function () {
    $review = Review::factory()->create();
    $review->delete();

    expect(Review::find($review->id))->toBeNull()
        ->and(Review::withTrashed()->find($review->id))->not->toBeNull();
});

it('flags admin users via isAdmin helper', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    expect($admin->isAdmin())->toBeTrue()
        ->and($user->isAdmin())->toBeFalse();
});
