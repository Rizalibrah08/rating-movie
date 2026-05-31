<?php

use App\Models\Genre;
use App\Models\Movie;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

it('blocks non-admin from accessing admin movie index', function () {
    $this->actingAs($this->user)->get('/admin/movies')->assertForbidden();
});

it('redirects guests to login', function () {
    $this->get('/admin/movies')->assertRedirect('/login');
});

it('renders admin movies index for admin', function () {
    Movie::factory(3)->create();

    $this->actingAs($this->admin)
        ->get('/admin/movies')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('admin/movies/Index')
            ->has('movies.data', 3)
        );
});

it('creates a movie with poster URL and no backdrop', function () {
    Storage::fake('public');
    $genre = Genre::factory()->create();

    $payload = [
        'title' => 'Pengejaran Senja',
        'synopsis' => 'Sinopsis cukup panjang.',
        'year' => 2024,
        'duration_min' => 120,
        'director' => 'Riri Riza',
        'poster_url' => 'https://example.com/p.jpg',
        'genres' => [$genre->id],
    ];

    $response = $this->actingAs($this->admin)->post('/admin/movies', $payload);

    $response->assertRedirect('/admin/movies');
    $movie = Movie::firstWhere('title', 'Pengejaran Senja');
    expect($movie)->not->toBeNull()
        ->and($movie->poster_url)->toBe('https://example.com/p.jpg')
        ->and($movie->poster_path)->toBeNull()
        ->and($movie->backdrop)->toBeNull()
        ->and($movie->genres()->count())->toBe(1);
});

it('creates a movie with uploaded poster file', function () {
    Storage::fake('public');

    $payload = [
        'title' => 'Film Upload',
        'synopsis' => 'Cerita uji upload poster.',
        'year' => 2023,
        'poster_file' => UploadedFile::fake()->image('poster.jpg', 600, 900),
    ];

    $this->actingAs($this->admin)->post('/admin/movies', $payload)->assertRedirect();

    $movie = Movie::firstWhere('title', 'Film Upload');
    expect($movie->poster_path)->not->toBeNull()
        ->and($movie->poster_url)->toBeNull();
    Storage::disk('public')->assertExists($movie->poster_path);
});

it('rejects movie creation when both poster modes are empty', function () {
    $payload = [
        'title' => 'Tanpa Poster',
        'synopsis' => 'Cerita tanpa poster.',
        'year' => 2023,
    ];

    $response = $this->actingAs($this->admin)->post('/admin/movies', $payload);

    $response->assertSessionHasErrors(['poster_file', 'poster_url']);
    expect(Movie::where('title', 'Tanpa Poster')->exists())->toBeFalse();
});

it('updates a movie title and switches poster from URL to file', function () {
    Storage::fake('public');
    $movie = Movie::factory()->create([
        'title' => 'Old Title',
        'poster_path' => null,
        'poster_url' => 'https://example.com/old.jpg',
    ]);

    $this->actingAs($this->admin)->post("/admin/movies/{$movie->id}", [
        '_method' => 'put',
        'title' => 'New Title',
        'synopsis' => $movie->synopsis,
        'year' => $movie->year,
        'poster_file' => UploadedFile::fake()->image('new.jpg', 600, 900),
    ])->assertRedirect('/admin/movies');

    $movie->refresh();
    expect($movie->title)->toBe('New Title')
        ->and($movie->poster_path)->not->toBeNull()
        ->and($movie->poster_url)->toBeNull();
});

it('deletes a movie and removes its files', function () {
    Storage::fake('public');
    $posterPath = UploadedFile::fake()->image('poster.jpg')->store('posters', 'public');
    $backdropPath = UploadedFile::fake()->image('back.jpg')->store('backdrops', 'public');

    $movie = Movie::factory()->create([
        'poster_path' => $posterPath,
        'poster_url' => null,
        'backdrop_path' => $backdropPath,
        'backdrop_url' => null,
    ]);

    Storage::disk('public')->assertExists($posterPath);

    $this->actingAs($this->admin)->delete("/admin/movies/{$movie->id}")->assertRedirect('/admin/movies');

    expect(Movie::find($movie->id))->toBeNull();
    Storage::disk('public')->assertMissing($posterPath);
    Storage::disk('public')->assertMissing($backdropPath);
});

it('lists genres and shows movies count for admin', function () {
    $genre = Genre::factory()->create(['name' => 'Drama']);
    Movie::factory(2)->create()->each(fn ($m) => $m->genres()->attach($genre->id));

    $this->actingAs($this->admin)
        ->get('/admin/genres')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('admin/genres/Index')
            ->has('genres')
            ->where('genres.0.movies_count', fn ($v) => $v >= 0)
        );
});

it('creates a new genre via admin', function () {
    $this->actingAs($this->admin)->post('/admin/genres', ['name' => 'Sci-Fi'])->assertRedirect();
    expect(Genre::where('name', 'Sci-Fi')->exists())->toBeTrue();
});

it('rejects duplicate genre name', function () {
    Genre::factory()->create(['name' => 'Aksi', 'slug' => 'aksi']);

    $this->actingAs($this->admin)->post('/admin/genres', ['name' => 'Aksi'])
        ->assertSessionHasErrors('name');
});

it('updates a genre name and regenerates slug', function () {
    $genre = Genre::factory()->create(['name' => 'Drama Lama', 'slug' => 'drama-lama']);

    $this->actingAs($this->admin)->put("/admin/genres/{$genre->id}", ['name' => 'Drama Baru'])->assertRedirect();

    $genre->refresh();
    expect($genre->name)->toBe('Drama Baru')->and($genre->slug)->toBe('drama-baru');
});

it('deletes a genre', function () {
    $genre = Genre::factory()->create();

    $this->actingAs($this->admin)->delete("/admin/genres/{$genre->id}")->assertRedirect();

    expect(Genre::find($genre->id))->toBeNull();
});
