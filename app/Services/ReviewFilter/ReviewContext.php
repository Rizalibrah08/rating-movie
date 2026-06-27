<?php

namespace App\Services\ReviewFilter;

/**
 * Snapshot data ulasan yang akan dievaluasi oleh pipeline.
 * Imutable — pipeline hanya membaca, tidak memodifikasi.
 */
final readonly class ReviewContext
{
    public function __construct(
        public ?int $userId,
        public int $movieId,
        public int $rating,
        public string $body,
        /** Body sudah dinormalisasi (lowercase + leetspeak basic + whitespace collapsed). */
        public string $normalizedBody,
        /** Hash body normalisasi — dipakai untuk deteksi duplikat. */
        public string $bodyHash,
        public ?string $ip = null,
        public int $trustScore = 0,
    ) {}
}
