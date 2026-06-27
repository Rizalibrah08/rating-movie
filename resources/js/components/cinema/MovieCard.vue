<script setup lang="ts">
/**
 * MovieCard — kartu poster film dengan ScoreBadge overlay.
 *
 * Layout:
 *   [POSTER (aspect 2:3)]  ← ScoreBadge sm di pojok kanan-bawah
 *   TITLE (Bebas Neue)
 *   YEAR · DURATION_MIN · GENRE
 *
 * Click → navigate to /movies/{slug}
 */
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import ScoreBadge from './ScoreBadge.vue';

interface Movie {
    id: number;
    title: string;
    slug: string;
    year?: number | null;
    duration_min?: number | null;
    poster?: string | null;
    backdrop?: string | null;
    avg_score?: number | null;
    review_count?: number;
    genres?: Array<{ id: number; name: string; slug: string }>;
}

interface Props {
    movie: Movie;
    /** Sembunyikan score badge bila skor belum ada (null) */
    hideScoreWhenNull?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    hideScoreWhenNull: false,
});

const showScore = props.movie.avg_score != null || !props.hideScoreWhenNull;
const genreLabel = props.movie.genres?.[0]?.name ?? null;

const imageError = ref(false);
</script>

<template>
    <Link
        :href="`/movies/${movie.slug}`"
        class="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--cinema-teal)] rounded-lg"
    >
        <div class="relative overflow-hidden rounded-lg bg-[var(--cinema-elevated)] aspect-[2/3] shadow-lg">
            <img
                v-if="movie.poster && !imageError"
                :src="movie.poster"
                :alt="movie.title"
                loading="lazy"
                @error="imageError = true"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
            />
            <div
                v-else
                class="flex h-full w-full flex-col items-center justify-center bg-gradient-to-br from-[var(--cinema-surface)] to-[var(--cinema-border)] p-4 text-center transition-transform duration-500 group-hover:scale-110"
            >
                <div class="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-[var(--cinema-elevated)] shadow-inner">
                    <span class="font-display text-2xl text-[var(--cinema-muted)]">{{ movie.title.substring(0, 2).toUpperCase() }}</span>
                </div>
                <span class="font-display text-sm tracking-widest text-[var(--cinema-muted)] opacity-50">{{ movie.title }}</span>
            </div>

            <!-- Gradient bawah untuk meningkatkan kontras ScoreBadge -->
            <div
                class="pointer-events-none absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-black/80 to-transparent"
            />

            <!-- ScoreBadge overlay -->
            <ScoreBadge
                v-if="showScore"
                :score="movie.avg_score"
                size="sm"
                class="absolute bottom-2 right-2"
            />
        </div>

        <div class="mt-3 space-y-1">
            <h3 class="font-display text-xl tracking-wide leading-tight text-[var(--cinema-text)] group-hover:text-[var(--cinema-teal)] transition-colors line-clamp-2">
                {{ movie.title }}
            </h3>
            <p class="text-xs text-[var(--cinema-muted)]">
                <span v-if="movie.year">{{ movie.year }}</span>
                <span v-if="movie.duration_min"> · {{ movie.duration_min }} min</span>
                <span v-if="genreLabel"> · {{ genreLabel }}</span>
            </p>
        </div>
    </Link>
</template>
