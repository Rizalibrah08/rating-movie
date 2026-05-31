<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMovieRequest;
use App\Http\Requests\Admin\UpdateMovieRequest;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MovieController extends Controller
{
    public function index(Request $request): Response
    {
        $movies = Movie::query()
            ->with('genres:id,name,slug')
            ->withCount(['reviews as published_count' => fn ($q) => $q->where('status', Review::STATUS_PUBLISHED)])
            ->withCount(['reviews as pending_count' => fn ($q) => $q->where('status', Review::STATUS_PENDING)])
            ->when($request->string('q')->trim()->toString(), fn ($q, $term) => $q->where('title', 'like', "%{$term}%"))
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Movie $m) => [
                'id' => $m->id,
                'title' => $m->title,
                'slug' => $m->slug,
                'year' => $m->year,
                'poster' => $m->poster,
                'has_backdrop' => $m->has_backdrop,
                'published_count' => $m->published_count,
                'pending_count' => $m->pending_count,
                'genres' => $m->genres->map(fn ($g) => ['id' => $g->id, 'name' => $g->name]),
            ]);

        return Inertia::render('admin/movies/Index', [
            'movies' => $movies,
            'filters' => ['q' => $request->string('q')->toString()],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/movies/Create', [
            'genres' => Genre::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreMovieRequest $request): RedirectResponse
    {
        $data = $request->safe()->only(['title', 'synopsis', 'year', 'duration_min', 'director', 'poster_url', 'backdrop_url']);

        if ($request->hasFile('poster_file')) {
            $data['poster_path'] = $request->file('poster_file')->store('posters', 'public');
            $data['poster_url'] = null; // Pilih file → bersihkan url field
        }
        if ($request->hasFile('backdrop_file')) {
            $data['backdrop_path'] = $request->file('backdrop_file')->store('backdrops', 'public');
            $data['backdrop_url'] = null;
        }

        $movie = Movie::create($data);

        if ($request->filled('genres')) {
            $movie->genres()->sync($request->validated('genres'));
        }

        return redirect()
            ->route('admin.movies.index')
            ->with('flash.success', "Film \"{$movie->title}\" berhasil ditambahkan.");
    }

    public function edit(Movie $movie): Response
    {
        $movie->load('genres:id');

        return Inertia::render('admin/movies/Edit', [
            'movie' => [
                'id' => $movie->id,
                'title' => $movie->title,
                'slug' => $movie->slug,
                'synopsis' => $movie->synopsis,
                'year' => $movie->year,
                'duration_min' => $movie->duration_min,
                'director' => $movie->director,
                'poster_path' => $movie->poster_path,
                'poster_url' => $movie->poster_url,
                'poster' => $movie->poster,
                'backdrop_path' => $movie->backdrop_path,
                'backdrop_url' => $movie->backdrop_url,
                'backdrop' => $movie->backdrop,
                'genre_ids' => $movie->genres->pluck('id'),
            ],
            'genres' => Genre::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateMovieRequest $request, Movie $movie): RedirectResponse
    {
        $data = $request->safe()->only(['title', 'synopsis', 'year', 'duration_min', 'director']);

        // Poster handling
        if ($request->boolean('remove_poster')) {
            if ($movie->poster_path) {
                Storage::disk('public')->delete($movie->poster_path);
            }
            $data['poster_path'] = null;
            $data['poster_url'] = null;
        }
        if ($request->hasFile('poster_file')) {
            if ($movie->poster_path) {
                Storage::disk('public')->delete($movie->poster_path);
            }
            $data['poster_path'] = $request->file('poster_file')->store('posters', 'public');
            $data['poster_url'] = null;
        } elseif ($request->filled('poster_url')) {
            // Switching to URL — clear existing file
            if ($movie->poster_path) {
                Storage::disk('public')->delete($movie->poster_path);
                $data['poster_path'] = null;
            }
            $data['poster_url'] = $request->validated('poster_url');
        }

        // Backdrop handling (opsional, boleh kosong total)
        if ($request->boolean('remove_backdrop')) {
            if ($movie->backdrop_path) {
                Storage::disk('public')->delete($movie->backdrop_path);
            }
            $data['backdrop_path'] = null;
            $data['backdrop_url'] = null;
        }
        if ($request->hasFile('backdrop_file')) {
            if ($movie->backdrop_path) {
                Storage::disk('public')->delete($movie->backdrop_path);
            }
            $data['backdrop_path'] = $request->file('backdrop_file')->store('backdrops', 'public');
            $data['backdrop_url'] = null;
        } elseif ($request->filled('backdrop_url')) {
            if ($movie->backdrop_path) {
                Storage::disk('public')->delete($movie->backdrop_path);
                $data['backdrop_path'] = null;
            }
            $data['backdrop_url'] = $request->validated('backdrop_url');
        }

        // Validasi tambahan: pastikan poster akhirnya ada (file atau url)
        $finalPosterPath = $data['poster_path'] ?? $movie->poster_path;
        $finalPosterUrl = $data['poster_url'] ?? $movie->poster_url;
        if (! $finalPosterPath && ! $finalPosterUrl) {
            return back()
                ->withErrors(['poster_file' => 'Film harus memiliki poster (file atau URL).'])
                ->withInput();
        }

        $movie->update($data);

        if ($request->has('genres')) {
            $movie->genres()->sync($request->validated('genres') ?? []);
        }

        return redirect()
            ->route('admin.movies.index')
            ->with('flash.success', "Film \"{$movie->title}\" berhasil diperbarui.");
    }

    public function destroy(Movie $movie): RedirectResponse
    {
        if ($movie->poster_path) {
            Storage::disk('public')->delete($movie->poster_path);
        }
        if ($movie->backdrop_path) {
            Storage::disk('public')->delete($movie->backdrop_path);
        }

        $title = $movie->title;
        $movie->delete();

        return redirect()
            ->route('admin.movies.index')
            ->with('flash.success', "Film \"{$title}\" telah dihapus.");
    }
}
