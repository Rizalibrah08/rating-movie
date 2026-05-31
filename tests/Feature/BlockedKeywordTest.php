<?php

use App\Models\BlockedKeyword;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

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

    Cache::flush();
});

it('blocks non-admin from blacklist admin page', function () {
    $this->actingAs($this->user)->get('/admin/keywords')->assertForbidden();
});

it('renders blacklist index for admin', function () {
    BlockedKeyword::factory(3)->create();

    $this->actingAs($this->admin)
        ->get('/admin/keywords')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('admin/keywords/Index')
            ->has('keywords.data', 3)
            ->has('stats')
            ->has('categories')
        );
});

it('creates a blocked keyword and normalizes it to lowercase', function () {
    $this->actingAs($this->admin)->post('/admin/keywords', [
        'keyword' => '  MENJIJIKKAN  ',
        'category' => 'insult',
        'is_active' => true,
    ])->assertRedirect();

    expect(BlockedKeyword::where('keyword', 'menjijikkan')->exists())->toBeTrue();
});

it('rejects duplicate keyword', function () {
    BlockedKeyword::factory()->create(['keyword' => 'gaje']);

    $this->actingAs($this->admin)->post('/admin/keywords', [
        'keyword' => 'gaje',
        'category' => 'spam',
    ])->assertSessionHasErrors('keyword');
});

it('rejects invalid category', function () {
    $this->actingAs($this->admin)->post('/admin/keywords', [
        'keyword' => 'kata',
        'category' => 'bukan_kategori',
    ])->assertSessionHasErrors('category');
});

it('updates a keyword', function () {
    $kw = BlockedKeyword::factory()->create(['keyword' => 'lama']);

    $this->actingAs($this->admin)->put("/admin/keywords/{$kw->id}", [
        'keyword' => 'baru',
        'category' => 'spam',
        'is_active' => false,
    ])->assertRedirect();

    $kw->refresh();
    expect($kw->keyword)->toBe('baru')
        ->and($kw->category)->toBe('spam')
        ->and($kw->is_active)->toBeFalse();
});

it('deletes a keyword', function () {
    $kw = BlockedKeyword::factory()->create();

    $this->actingAs($this->admin)->delete("/admin/keywords/{$kw->id}")->assertRedirect();

    expect(BlockedKeyword::find($kw->id))->toBeNull();
});

it('caches active list and invalidates on save', function () {
    BlockedKeyword::factory()->create(['keyword' => 'kata1', 'is_active' => true]);

    $list1 = BlockedKeyword::activeList();
    expect($list1)->toHaveCount(1);

    // Cache hit (no new keyword created in DB but cached value persists)
    BlockedKeyword::factory()->create(['keyword' => 'kata2', 'is_active' => true]);

    // After save, cache should be invalidated; activeList should now include both
    $list2 = BlockedKeyword::activeList();
    expect($list2)->toHaveCount(2);
});

it('only includes active keywords in activeList', function () {
    BlockedKeyword::factory()->create(['keyword' => 'aktif', 'is_active' => true]);
    BlockedKeyword::factory()->inactive()->create(['keyword' => 'nonaktif']);

    $list = BlockedKeyword::activeList();
    expect($list)->toHaveCount(1)
        ->and($list[0]['keyword'])->toBe('aktif');
});

it('seeds default blacklist keywords', function () {
    $this->seed(\Database\Seeders\BlockedKeywordSeeder::class);

    expect(BlockedKeyword::count())->toBeGreaterThanOrEqual(20)
        ->and(BlockedKeyword::where('keyword', 'mending nonton')->exists())->toBeTrue()
        ->and(BlockedKeyword::where('keyword', 'menjijikkan')->exists())->toBeTrue()
        // Pastikan kata kritik wajar TIDAK masuk default (sesuai weakness analysis #1)
        ->and(BlockedKeyword::where('keyword', 'jelek')->exists())->toBeFalse()
        ->and(BlockedKeyword::where('keyword', 'buruk')->exists())->toBeFalse();
});
