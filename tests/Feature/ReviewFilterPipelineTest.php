<?php

use App\Models\BlockedKeyword;
use App\Models\Movie;
use App\Models\Review;
use App\Models\User;
use App\Services\ReviewFilter\Pipeline;
use App\Services\ReviewFilter\ReviewContext;
use App\Services\ReviewFilter\Rules\BlacklistKeywordRule;
use App\Services\ReviewFilter\Rules\ContentDuplicateRule;
use App\Services\ReviewFilter\Rules\CooldownRule;
use App\Services\ReviewFilter\Rules\HourlyQuotaRule;
use App\Services\ReviewFilter\Rules\LengthRule;
use App\Services\ReviewFilter\Rules\OnePerMovieRule;
use App\Services\ReviewFilter\Rules\UrlDetectionRule;
use App\Services\ReviewFilter\Rules\VowelRule;
use App\Services\ReviewFilter\TextNormalizer;
use Illuminate\Support\Facades\Cache;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

function ctx(string $body, ?int $userId = null, int $movieId = 1, int $rating = 70): ReviewContext
{
    return new ReviewContext(
        userId: $userId,
        movieId: $movieId,
        rating: $rating,
        body: $body,
        normalizedBody: TextNormalizer::normalize($body),
        bodyHash: TextNormalizer::canonicalHash($body),
        ip: '127.0.0.1',
    );
}

// === LengthRule ===
it('rejects body shorter than 30 characters', function () {
    $rule = new LengthRule;
    $result = $rule->check(ctx('Pendek banget.'));
    expect($result->passed)->toBeFalse()
        ->and($result->rule)->toBe('length');
});

it('rejects body with fewer than 5 meaningful words', function () {
    $rule = new LengthRule;
    // 30+ char tapi cuma 1 kata bermakna ("aaaaaa…")
    $result = $rule->check(ctx(str_repeat('a', 35)));
    expect($result->passed)->toBeFalse();
});

it('passes body with sufficient length and word count', function () {
    $rule = new LengthRule;
    $result = $rule->check(ctx('Film ini sangat menarik dan layak ditonton oleh semua orang.'));
    expect($result->passed)->toBeTrue();
});

// === VowelRule ===
it('rejects body without any vowel', function () {
    $rule = new VowelRule;
    $result = $rule->check(ctx('zzzz tttt rrrr ssss vvvv'));
    expect($result->passed)->toBeFalse()
        ->and($result->rule)->toBe('vowel');
});

it('passes body with vowels', function () {
    $rule = new VowelRule;
    expect($rule->check(ctx('Film ini bagus sekali dan menyentuh hati'))->passed)->toBeTrue();
});

// === BlacklistKeywordRule ===
it('rejects body containing blacklisted keyword', function () {
    BlockedKeyword::factory()->create(['keyword' => 'sampah', 'is_active' => true]);

    $rule = new BlacklistKeywordRule;
    $result = $rule->check(ctx('film ini benar-benar sampah dan tidak layak'));
    expect($result->passed)->toBeFalse()
        ->and($result->reason)->toContain('sampah');
});

it('catches leetspeak bypass via normalization', function () {
    BlockedKeyword::factory()->create(['keyword' => 'jelek', 'is_active' => true]);

    $rule = new BlacklistKeywordRule;
    // "j3l3k" akan dinormalize jadi "jelek"
    $result = $rule->check(ctx('film ini j3l3k banget pokoknya'));
    expect($result->passed)->toBeFalse();
});

it('skips keyword preceded by negation word', function () {
    BlockedKeyword::factory()->create(['keyword' => 'menjijikkan', 'is_active' => true]);

    $rule = new BlacklistKeywordRule;
    expect($rule->check(ctx('film ini tidak menjijikkan sama sekali, justru bagus'))->passed)->toBeTrue();
});

it('only triggers on whole word boundaries', function () {
    BlockedKeyword::factory()->create(['keyword' => 'racun', 'is_active' => true]);

    $rule = new BlacklistKeywordRule;
    // "kacumacuna" tidak boleh trigger meski mengandung "acun"
    expect($rule->check(ctx('film tentang kacumacuna saja yang aneh'))->passed)->toBeTrue();
    expect($rule->check(ctx('plotnya seperti racun bagi penonton'))->passed)->toBeFalse();
});

it('catches multi-word phrases', function () {
    BlockedKeyword::factory()->create(['keyword' => 'mending nonton', 'is_active' => true]);

    $rule = new BlacklistKeywordRule;
    expect($rule->check(ctx('mending nonton yang lain daripada ini'))->passed)->toBeFalse();
});

// === Bypass celah #1: Spasi antar huruf ===
it('catches spaced-out bypass via SeparatedCharsRule: "j e l e k"', function () {
    BlockedKeyword::factory()->create(['keyword' => 'jelek', 'is_active' => true]);

    $rule = new \App\Services\ReviewFilter\Rules\SeparatedCharsRule;
    $result = $rule->check(ctx('film ini j e l e k banget menurut saya'));
    expect($result->passed)->toBeFalse()
        ->and($result->rule)->toBe('separated_chars');
});

it('catches spaced-out bypass: "m e n j i j i k k a n"', function () {
    BlockedKeyword::factory()->create(['keyword' => 'menjijikkan', 'is_active' => true]);

    $rule = new \App\Services\ReviewFilter\Rules\SeparatedCharsRule;
    expect($rule->check(ctx('film ini m e n j i j i k k a n sekali'))->passed)->toBeFalse();
});

it('SeparatedCharsRule passes on normal sentences', function () {
    BlockedKeyword::factory()->create(['keyword' => 'jelek', 'is_active' => true]);

    $rule = new \App\Services\ReviewFilter\Rules\SeparatedCharsRule;
    expect($rule->check(ctx('film ini sangat bagus dan layak ditonton'))->passed)->toBeTrue();
});

// === Bypass celah #2: Separator antar huruf ===
it('catches separator bypass via TextNormalizer: "j.e.l.e.k"', function () {
    BlockedKeyword::factory()->create(['keyword' => 'jelek', 'is_active' => true]);

    $rule = new BlacklistKeywordRule;
    $result = $rule->check(ctx('film ini j.e.l.e.k banget menurut saya'));
    expect($result->passed)->toBeFalse();
});

it('catches dash separator bypass: "j-e-l-e-k"', function () {
    BlockedKeyword::factory()->create(['keyword' => 'jelek', 'is_active' => true]);

    $rule = new BlacklistKeywordRule;
    expect($rule->check(ctx('film ini j-e-l-e-k banget menurut saya'))->passed)->toBeFalse();
});

it('catches underscore separator bypass: "j_e_l_e_k"', function () {
    BlockedKeyword::factory()->create(['keyword' => 'jelek', 'is_active' => true]);

    $rule = new BlacklistKeywordRule;
    expect($rule->check(ctx('film ini j_e_l_e_k banget menurut saya'))->passed)->toBeFalse();
});

// === Bypass celah #3: Homoglyph Unicode ===
it('catches cyrillic homoglyph bypass: "јеlеk"', function () {
    BlockedKeyword::factory()->create(['keyword' => 'jelek', 'is_active' => true]);

    $rule = new BlacklistKeywordRule;
    // ј = Cyrillic je, е = Cyrillic e
    $result = $rule->check(ctx("film ini \u{0458}\u{0435}l\u{0435}k banget menurut saya"));
    expect($result->passed)->toBeFalse();
});

// === Bypass celah #5: Pengulangan ganda ===
it('catches double-repeat bypass: "jeleek"', function () {
    BlockedKeyword::factory()->create(['keyword' => 'jelek', 'is_active' => true]);

    $rule = new BlacklistKeywordRule;
    expect($rule->check(ctx('film ini jeleek banget menurut saya'))->passed)->toBeFalse();
});

it('catches triple-repeat bypass: "jeleeek"', function () {
    BlockedKeyword::factory()->create(['keyword' => 'jelek', 'is_active' => true]);

    $rule = new BlacklistKeywordRule;
    expect($rule->check(ctx('film ini jeleeek banget menurut saya'))->passed)->toBeFalse();
});

// === Bypass celah #6: Zero-width characters ===
it('catches zero-width space bypass: "je​lek"', function () {
    BlockedKeyword::factory()->create(['keyword' => 'jelek', 'is_active' => true]);

    $rule = new BlacklistKeywordRule;
    // \u200B = zero-width space
    $result = $rule->check(ctx("film ini je\u{200B}lek banget menurut saya"));
    expect($result->passed)->toBeFalse();
});

// === Bypass celah #7: Diacritics/accent ===
it('catches diacritics bypass: "jélék"', function () {
    BlockedKeyword::factory()->create(['keyword' => 'jelek', 'is_active' => true]);

    $rule = new BlacklistKeywordRule;
    expect($rule->check(ctx('film ini jélék banget menurut saya'))->passed)->toBeFalse();
});

// === Bypass celah #4: Extended leet ===
it('catches extended leet bypass: "j€l€k"', function () {
    BlockedKeyword::factory()->create(['keyword' => 'jelek', 'is_active' => true]);

    $rule = new BlacklistKeywordRule;
    expect($rule->check(ctx('film ini j€l€k banget menurut saya'))->passed)->toBeFalse();
});

// === IpRateLimitRule ===
it('IpRateLimitRule flags when IP exceeds flag threshold', function () {
    $rule = new \App\Services\ReviewFilter\Rules\IpRateLimitRule(flagThreshold: 2, rejectThreshold: 10, windowMinutes: 60);

    // Use a unique IP to avoid cache collision
    $ip = '192.168.99.' . rand(1, 254);
    $mkCtx = fn () => new \App\Services\ReviewFilter\ReviewContext(
        userId: null,
        movieId: 1,
        rating: 70,
        body: 'test body',
        normalizedBody: 'test body',
        bodyHash: 'hash',
        ip: $ip,
    );

    $rule->check($mkCtx()); // 1
    $rule->check($mkCtx()); // 2 — hits flag threshold
    $result = $rule->check($mkCtx()); // 3

    expect($result->passed)->toBeFalse()
        ->and($result->severity)->toBe('flag');
});

it('IpRateLimitRule rejects when IP exceeds reject threshold', function () {
    $rule = new \App\Services\ReviewFilter\Rules\IpRateLimitRule(flagThreshold: 1, rejectThreshold: 2, windowMinutes: 60);

    $ip = '10.0.99.' . rand(1, 254);
    $mkCtx = fn () => new \App\Services\ReviewFilter\ReviewContext(
        userId: null,
        movieId: 1,
        rating: 70,
        body: 'test body',
        normalizedBody: 'test body',
        bodyHash: 'hash',
        ip: $ip,
    );

    $rule->check($mkCtx()); // 1
    $rule->check($mkCtx()); // 2 — hits reject threshold
    $result = $rule->check($mkCtx()); // 3

    expect($result->passed)->toBeFalse()
        ->and($result->severity)->toBe('reject');
});

// === Feature: Regex-based Keywords ===
it('matches regex keywords', function () {
    // Regex matches any word ending in "anjing" like "menganjing"
    BlockedKeyword::factory()->create(['keyword' => '\w*anjing', 'is_active' => true, 'is_regex' => true]);

    $rule = new BlacklistKeywordRule;
    expect($rule->check(ctx('film ini menganjing sekali'))->passed)->toBeFalse();
    expect($rule->check(ctx('film ini anjing sekali'))->passed)->toBeFalse();
    expect($rule->check(ctx('situs ini aman http://aman.com'))->passed)->toBeTrue();
});

// === Feature: Whitelist/Exception ===
it('ignores blacklisted words if they are part of a whitelist phrase', function () {
    BlockedKeyword::factory()->create(['keyword' => 'gila', 'is_active' => true]);
    BlockedKeyword::factory()->create(['keyword' => 'ide gila', 'category' => 'whitelist', 'is_active' => true]);

    $rule = new BlacklistKeywordRule;
    
    // "beneran gila nih film" -> matches "gila", rejected
    expect($rule->check(ctx('beneran gila nih film'))->passed)->toBeFalse();
    
    // "ide gila dari sutradaranya" -> "ide gila" is removed by whitelist, no "gila" found -> passed
    expect($rule->check(ctx('ide gila dari sutradaranya'))->passed)->toBeTrue();
});

// === Feature: Trust Score skipping ===
it('skips some rules when user has high trust score', function () {
    $pipeline = new \App\Services\ReviewFilter\Pipeline([
        new \App\Services\ReviewFilter\Rules\UrlDetectionRule,
    ]);

    // Normal user (trustScore = 0) -> URL detection runs and fails
    $ctxNormal = new \App\Services\ReviewFilter\ReviewContext(
        userId: 1, movieId: 1, rating: 50,
        body: 'visit http://spam.com',
        normalizedBody: 'visit http://spam.com',
        bodyHash: 'hash', ip: '127.0.0.1', trustScore: 0
    );
    expect($pipeline->run($ctxNormal)->passed)->toBeFalse();

    // High trust user (trustScore = 100) -> URL detection skipped -> passes
    $ctxHighTrust = new \App\Services\ReviewFilter\ReviewContext(
        userId: 1, movieId: 1, rating: 50,
        body: 'visit http://spam.com',
        normalizedBody: 'visit http://spam.com',
        bodyHash: 'hash', ip: '127.0.0.1', trustScore: 100
    );
    expect($pipeline->run($ctxHighTrust)->passed)->toBeTrue();
});

// === Feature: Fuzzy Matching (Levenshtein) ===
it('catches typo bypass via fuzzy matching (distance 1)', function () {
    BlockedKeyword::factory()->create(['keyword' => 'sampah', 'is_active' => true]);

    $rule = new BlacklistKeywordRule;
    // 'sumpah' is 1 edit distance from 'sampah' (if 'sumpah' is not meant, but this tests the mechanics)
    // Actually let's use 'sampah' -> 'sampaj' (distance 1)
    expect($rule->check(ctx('film ini beneran sampaj banget'))->passed)->toBeFalse();
});

it('catches typo bypass via fuzzy matching (distance 2 for long words)', function () {
    BlockedKeyword::factory()->create(['keyword' => 'menjijikkan', 'is_active' => true]);

    $rule = new BlacklistKeywordRule;
    // 'menjijikkan' (11 chars) -> 'menjijikan' (10 chars, distance 1)
    // Wait, TextNormalizer compresses 'menjijikkan' to 'menjijikan' anyway.
    // Let's use 'bajingan' (8 chars) -> 'bajingam' (distance 1) -> 'bajingxm' (distance 2)
    BlockedKeyword::factory()->create(['keyword' => 'bajingan', 'is_active' => true]);
    expect($rule->check(ctx('film ini bajingxm banget'))->passed)->toBeFalse();
});

// === UrlDetectionRule ===
it('rejects body containing http url', function () {
    $rule = new UrlDetectionRule;
    expect($rule->check(ctx('cek di https://example.com/movie segera'))->passed)->toBeFalse();
});

it('rejects body containing www domain', function () {
    $rule = new UrlDetectionRule;
    expect($rule->check(ctx('kunjungi www.spammer.com untuk gratis'))->passed)->toBeFalse();
});

it('rejects bare domain like example.com', function () {
    $rule = new UrlDetectionRule;
    expect($rule->check(ctx('promo ada di example.com gratis ya'))->passed)->toBeFalse();
});

it('passes body without url', function () {
    $rule = new UrlDetectionRule;
    expect($rule->check(ctx('Film bagus, akting natural, sinematografi indah'))->passed)->toBeTrue();
});

// === Pipeline integration ===
it('runs all rules and returns first failure', function () {
    BlockedKeyword::factory()->create(['keyword' => 'sampah']);

    $pipeline = (new Pipeline)
        ->pipe(new LengthRule)
        ->pipe(new VowelRule)
        ->pipe(new UrlDetectionRule)
        ->pipe(new BlacklistKeywordRule);

    // Length fails first
    $r1 = $pipeline->run(ctx('Pendek'));
    expect($r1->rule)->toBe('length');

    // Length passes, blacklist fails
    $r2 = $pipeline->run(ctx('Film ini benar-benar sampah dan tidak menarik sama sekali'));
    expect($r2->rule)->toBe('blacklist_keyword');

    // All pass
    $r3 = $pipeline->run(ctx('Film ini sangat bagus dan menarik dari awal hingga akhir'));
    expect($r3->passed)->toBeTrue();
});

// === Frequency rules (Task 7) ===
it('CooldownRule rejects when last review is within window', function () {
    $user = User::factory()->create();
    Review::factory()->create([
        'user_id' => $user->id,
        'created_at' => now()->subSeconds(30),
    ]);

    $rule = new CooldownRule(cooldownSeconds: 60);
    expect($rule->check(ctx('body', userId: $user->id))->passed)->toBeFalse();
});

it('CooldownRule passes when last review is older than window', function () {
    $user = User::factory()->create();
    Review::factory()->create([
        'user_id' => $user->id,
        'created_at' => now()->subSeconds(120),
    ]);

    $rule = new CooldownRule(cooldownSeconds: 60);
    expect($rule->check(ctx('body', userId: $user->id))->passed)->toBeTrue();
});

it('OnePerMovieRule rejects when user already has published review for movie', function () {
    $user = User::factory()->create();
    $movie = Movie::factory()->create();
    Review::factory()->published()->create(['user_id' => $user->id, 'movie_id' => $movie->id]);

    $rule = new OnePerMovieRule;
    expect($rule->check(ctx('body', userId: $user->id, movieId: $movie->id))->passed)->toBeFalse();
});

it('OnePerMovieRule allows resubmit when previous was rejected', function () {
    $user = User::factory()->create();
    $movie = Movie::factory()->create();
    Review::factory()->rejected()->create(['user_id' => $user->id, 'movie_id' => $movie->id]);

    $rule = new OnePerMovieRule;
    expect($rule->check(ctx('body', userId: $user->id, movieId: $movie->id))->passed)->toBeTrue();
});

it('ContentDuplicateRule rejects identical text by same user across different movies', function () {
    $user = User::factory()->create();
    $movieA = Movie::factory()->create();
    $movieB = Movie::factory()->create();
    $body = 'Saya tonton film ini sangat keren sekali, layak banget ditonton ulang.';
    Review::factory()->create(['user_id' => $user->id, 'movie_id' => $movieA->id, 'body' => $body]);

    $rule = new ContentDuplicateRule;
    // Submit the exact same text to a different movie
    $result = $rule->check(ctx($body, userId: $user->id, movieId: $movieB->id));
    expect($result->passed)->toBeFalse();
});

it('HourlyQuotaRule flags (not rejects) when threshold exceeded', function () {
    $user = User::factory()->create();
    Review::factory(5)->create(['user_id' => $user->id, 'created_at' => now()->subMinutes(10)]);

    $rule = new HourlyQuotaRule(threshold: 5, windowMinutes: 60);
    $result = $rule->check(ctx('body', userId: $user->id));

    expect($result->passed)->toBeFalse()
        ->and($result->severity)->toBe('flag')
        ->and($result->rule)->toBe('hourly_quota');
});

it('HourlyQuotaRule passes when below threshold', function () {
    $user = User::factory()->create();
    Review::factory(2)->create(['user_id' => $user->id, 'created_at' => now()->subMinutes(10)]);

    $rule = new HourlyQuotaRule;
    expect($rule->check(ctx('body', userId: $user->id))->passed)->toBeTrue();
});
