<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ScoreBadge from '@/components/cinema/ScoreBadge.vue';
import MovieCard from '@/components/cinema/MovieCard.vue';
import GlassPanel from '@/components/cinema/GlassPanel.vue';

const sampleMovies = [
    {
        id: 1,
        title: 'Pengejaran Senja',
        slug: 'pengejaran-senja',
        year: 2024,
        duration_min: 142,
        poster: 'https://picsum.photos/seed/movie-a/600/900',
        avg_score: 87,
        review_count: 124,
        genres: [{ id: 1, name: 'Drama', slug: 'drama' }],
    },
    {
        id: 2,
        title: 'Reruntuhan Kota',
        slug: 'reruntuhan-kota',
        year: 2023,
        duration_min: 118,
        poster: 'https://picsum.photos/seed/movie-b/600/900',
        avg_score: 62,
        review_count: 87,
        genres: [{ id: 2, name: 'Aksi', slug: 'aksi' }],
    },
    {
        id: 3,
        title: 'Cinta Tanpa Wajah',
        slug: 'cinta-tanpa-wajah',
        year: 2022,
        duration_min: 95,
        poster: 'https://picsum.photos/seed/movie-c/600/900',
        avg_score: 34,
        review_count: 41,
        genres: [{ id: 3, name: 'Romance', slug: 'romance' }],
    },
    {
        id: 4,
        title: 'Belum Direview',
        slug: 'belum-direview',
        year: 2025,
        duration_min: 105,
        poster: 'https://picsum.photos/seed/movie-d/600/900',
        avg_score: null,
        review_count: 0,
        genres: [{ id: 4, name: 'Misteri', slug: 'misteri' }],
    },
];

const palette = [
    { name: 'cinema-base', value: 'var(--cinema-base)', hex: '#14181c' },
    { name: 'cinema-surface', value: 'var(--cinema-surface)', hex: '#1c2228' },
    { name: 'cinema-elevated', value: 'var(--cinema-elevated)', hex: '#222a31' },
    { name: 'cinema-border', value: 'var(--cinema-border)', hex: '#2c343c' },
    { name: 'cinema-text', value: 'var(--cinema-text)', hex: '#e9edf0' },
    { name: 'cinema-muted', value: 'var(--cinema-muted)', hex: '#9aa7b3' },
    { name: 'cinema-teal', value: 'var(--cinema-teal)', hex: '#00c1a9' },
    { name: 'cinema-orange', value: 'var(--cinema-orange)', hex: '#ff8000' },
    { name: 'score-green', value: 'var(--score-green)', hex: '#66cc33' },
    { name: 'score-yellow', value: 'var(--score-yellow)', hex: '#ffcc33' },
    { name: 'score-red', value: 'var(--score-red)', hex: '#ff4d4d' },
    { name: 'score-grey', value: 'var(--score-grey)', hex: '#6b7280' },
];
</script>

<template>
    <Head title="Design System Preview" />
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 space-y-16">
        <header>
            <h1 class="font-display text-5xl tracking-wide">DESIGN SYSTEM</h1>
            <p class="text-[var(--cinema-muted)] mt-2">Halaman preview komponen cinematic dark theme. Hanya tersedia di mode non-production.</p>
        </header>

        <!-- Typography -->
        <section>
            <h2 class="font-display text-2xl tracking-wide mb-4 border-b border-[var(--cinema-border)] pb-2">Typography</h2>
            <div class="space-y-3">
                <div>
                    <p class="text-xs uppercase tracking-wider text-[var(--cinema-muted)] mb-1">Display · Bebas Neue</p>
                    <p class="font-display text-6xl tracking-wide">PENGEJARAN SENJA</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-[var(--cinema-muted)] mb-1">UI / Body · Inter</p>
                    <p class="text-base">Sebuah film tentang waktu yang berhenti dan dua hati yang akhirnya bertemu di stasiun terakhir.</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-[var(--cinema-muted)] mb-1">Mono · JetBrains Mono</p>
                    <p class="font-mono text-2xl">87 / 100</p>
                </div>
            </div>
        </section>

        <!-- Color palette -->
        <section>
            <h2 class="font-display text-2xl tracking-wide mb-4 border-b border-[var(--cinema-border)] pb-2">Color Palette</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-4">
                <div v-for="c in palette" :key="c.name" class="space-y-2">
                    <div
                        class="h-20 rounded-lg border border-[var(--cinema-border)]"
                        :style="{ backgroundColor: c.value }"
                    />
                    <div class="text-xs">
                        <p class="font-mono">{{ c.name }}</p>
                        <p class="font-mono text-[var(--cinema-muted)]">{{ c.hex }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ScoreBadge -->
        <section>
            <h2 class="font-display text-2xl tracking-wide mb-4 border-b border-[var(--cinema-border)] pb-2">ScoreBadge</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <p class="text-xs uppercase tracking-wider text-[var(--cinema-muted)] mb-3">Color mapping (size md)</p>
                    <div class="flex items-end gap-4">
                        <div class="flex flex-col items-center gap-2">
                            <ScoreBadge :score="92" size="md" />
                            <p class="text-xs">92 → green</p>
                        </div>
                        <div class="flex flex-col items-center gap-2">
                            <ScoreBadge :score="60" size="md" />
                            <p class="text-xs">60 → yellow</p>
                        </div>
                        <div class="flex flex-col items-center gap-2">
                            <ScoreBadge :score="35" size="md" />
                            <p class="text-xs">35 → red</p>
                        </div>
                        <div class="flex flex-col items-center gap-2">
                            <ScoreBadge :score="null" size="md" />
                            <p class="text-xs">null → grey</p>
                        </div>
                    </div>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-[var(--cinema-muted)] mb-3">Size variants</p>
                    <div class="flex items-end gap-4">
                        <div class="flex flex-col items-center gap-2">
                            <ScoreBadge :score="87" size="sm" />
                            <p class="text-xs">sm</p>
                        </div>
                        <div class="flex flex-col items-center gap-2">
                            <ScoreBadge :score="87" size="md" />
                            <p class="text-xs">md</p>
                        </div>
                        <div class="flex flex-col items-center gap-2">
                            <ScoreBadge :score="87" size="lg" />
                            <p class="text-xs">lg</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- MovieCard -->
        <section>
            <h2 class="font-display text-2xl tracking-wide mb-4 border-b border-[var(--cinema-border)] pb-2">MovieCard</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <MovieCard v-for="m in sampleMovies" :key="m.id" :movie="m" />
            </div>
        </section>

        <!-- GlassPanel -->
        <section>
            <h2 class="font-display text-2xl tracking-wide mb-4 border-b border-[var(--cinema-border)] pb-2">GlassPanel</h2>
            <div
                class="relative rounded-xl overflow-hidden p-12"
                style="background-image: url('https://picsum.photos/seed/glass-bg/1600/600'); background-size: cover; background-position: center;"
            >
                <GlassPanel class="p-6">
                    <h3 class="font-display text-2xl mb-2">Subtle Glass</h3>
                    <p class="text-sm text-[var(--cinema-text)]/90">
                        Variant default, dipakai untuk ReviewCard di dalam container besar.
                    </p>
                </GlassPanel>
            </div>
        </section>
    </div>
</template>
