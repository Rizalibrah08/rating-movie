<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlockedKeywordRequest;
use App\Http\Requests\Admin\UpdateBlockedKeywordRequest;
use App\Models\BlockedKeyword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlockedKeywordController extends Controller
{
    public function index(Request $request): Response
    {
        $query = BlockedKeyword::query();

        if ($q = $request->string('q')->trim()->toString()) {
            $query->where('keyword', 'like', "%{$q}%");
        }
        if ($cat = $request->string('category')->toString()) {
            if (in_array($cat, BlockedKeyword::CATEGORIES, true)) {
                $query->where('category', $cat);
            }
        }
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $keywords = $query
            ->orderBy('category')
            ->orderBy('keyword')
            ->paginate(50)
            ->withQueryString();

        // Hitung jumlah aktif per kategori untuk dashboard kecil
        $countsByCategory = BlockedKeyword::query()
            ->selectRaw('category, SUM(CAST(is_active AS INTEGER)) as active, COUNT(*) as total')
            ->groupBy('category')
            ->get()
            ->keyBy('category');

        return Inertia::render('admin/keywords/Index', [
            'keywords' => $keywords,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'category' => $request->string('category')->toString(),
                'active' => $request->has('active') ? $request->boolean('active') : null,
            ],
            'categories' => BlockedKeyword::CATEGORIES,
            'stats' => collect(BlockedKeyword::CATEGORIES)->mapWithKeys(fn ($cat) => [
                $cat => [
                    'active' => (int) ($countsByCategory[$cat]->active ?? 0),
                    'total' => (int) ($countsByCategory[$cat]->total ?? 0),
                ],
            ]),
        ]);
    }

    public function store(StoreBlockedKeywordRequest $request): RedirectResponse
    {
        BlockedKeyword::create([
            'keyword' => $request->validated('keyword'),
            'category' => $request->validated('category'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('flash.success', 'Kata kunci ditambahkan ke blacklist.');
    }

    public function update(UpdateBlockedKeywordRequest $request, BlockedKeyword $blockedKeyword): RedirectResponse
    {
        $blockedKeyword->update([
            'keyword' => $request->validated('keyword'),
            'category' => $request->validated('category'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('flash.success', 'Kata kunci diperbarui.');
    }

    public function destroy(BlockedKeyword $blockedKeyword): RedirectResponse
    {
        $word = $blockedKeyword->keyword;
        $blockedKeyword->delete();

        return back()->with('flash.success', "Kata kunci \"{$word}\" dihapus.");
    }
}
