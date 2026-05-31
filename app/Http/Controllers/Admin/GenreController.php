<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGenreRequest;
use App\Http\Requests\Admin\UpdateGenreRequest;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GenreController extends Controller
{
    public function index(): Response
    {
        $genres = Genre::query()
            ->withCount('movies')
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Genre $g) => [
                'id' => $g->id,
                'name' => $g->name,
                'slug' => $g->slug,
                'movies_count' => $g->movies_count,
            ]);

        return Inertia::render('admin/genres/Index', ['genres' => $genres]);
    }

    public function store(StoreGenreRequest $request): RedirectResponse
    {
        Genre::create(['name' => $request->validated('name')]);

        return back()->with('flash.success', 'Genre baru berhasil ditambahkan.');
    }

    public function update(UpdateGenreRequest $request, Genre $genre): RedirectResponse
    {
        $genre->update([
            'name' => $request->validated('name'),
            'slug' => null, // observer akan auto-fill ulang
        ]);

        return back()->with('flash.success', 'Genre diperbarui.');
    }

    public function destroy(Genre $genre): RedirectResponse
    {
        $name = $genre->name;
        $genre->delete();

        return back()->with('flash.success', "Genre \"{$name}\" dihapus.");
    }
}
