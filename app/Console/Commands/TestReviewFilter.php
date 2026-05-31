<?php

namespace App\Console\Commands;

use App\Services\ReviewFilter\Pipeline;
use App\Services\ReviewFilter\ReviewContext;
use App\Services\ReviewFilter\TextNormalizer;
use Illuminate\Console\Command;

class TestReviewFilter extends Command
{
    protected $signature = 'review:test-filter
        {body : Teks ulasan yang ingin diuji (gunakan tanda kutip untuk multi-kata)}
        {--user-id=0 : Simulasikan submission dari user_id ini (0 = anonim, frequency rules dilewati)}
        {--movie-id=1 : Simulasikan submission untuk movie_id ini}
        {--rating=70 : Skor 0..100}';

    protected $description = 'Demo: jalankan ReviewFilter pipeline pada teks input dan tampilkan hasilnya.';

    public function handle(Pipeline $pipeline): int
    {
        $body = (string) $this->argument('body');
        $userId = (int) $this->option('user-id') ?: null;
        $movieId = (int) $this->option('movie-id');
        $rating = (int) $this->option('rating');

        $ctx = new ReviewContext(
            userId: $userId,
            movieId: $movieId,
            rating: $rating,
            body: $body,
            normalizedBody: TextNormalizer::normalize($body),
            bodyHash: TextNormalizer::canonicalHash($body),
            ip: '127.0.0.1',
        );

        $this->info('Body original:    '.$body);
        $this->line('Body normalized:  '.$ctx->normalizedBody);
        $this->line('Word count:       '.TextNormalizer::meaningfulWordCount($body));
        $this->line('Hash:             '.substr($ctx->bodyHash, 0, 12).'…');
        $this->newLine();

        $result = $pipeline->run($ctx);

        if ($result->passed) {
            $this->info('✓ PASS — semua rule lolos. Review akan dipublish.');

            return self::SUCCESS;
        }

        $color = $result->severity === 'flag' ? 'comment' : 'error';
        $label = strtoupper($result->severity);
        $this->{$color}("✗ {$label} — rule: {$result->rule}");
        $this->line('  Alasan: '.$result->reason);

        return self::SUCCESS;
    }
}
