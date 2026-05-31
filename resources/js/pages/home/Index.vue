<script setup lang="ts">
/**
 * Home page — cinematic landing.
 * - Hero rotator (auto-cycle 6s) hanya film dengan backdrop
 * - Section "Popular This Week" horizontal scroll
 * - Section "Recently Reviewed" grid MovieCard
 * - Activity feed: ReviewCard kompak
 */
import { Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import HeroBackdrop from '@/components/cinema/HeroBackdrop.vue';
import MovieCard from '@/components/cinema/MovieCard.vue';
import ScoreBadge from '@/components/cinema/ScoreBadge.vue';

interface HeroMovie {
    id: number;
    title: string;
    slug: string;
    synopsis: string;
    year: number | null;
    duration_min: number | null;
    poster: string | null;
    backdrop: string | null;
    avg_score: number | null;
    review_count: number;
}

interface ListMovie {
    id: number;
    title: string;
    slug: string;
    year: number | null;
    duration_min: number | null;
    poster: string | null;
    avg_score: number | null;
    review_count: number;
    genres: Array<{ id: number; name: string; slug: string }>;
}

interface FeedReview {
    id: number;
    rating: number;
    body: string;
    created_at: string | null;
    user: { id: number; name: string };
    movie: { id: number; title: string; slug: string; poster: string | null };
}

const props = defineProps<{
    heroMovies: HeroMovie[];
    popularThisWeek: ListMovie[];
    recentReviews: FeedReview[];
}>();

// Hero rotator state
const activeIndex = ref(0);
const activeHero = computed(() => props.heroMovies[activeIndex.value] ?? null);

let rotateTimer: ReturnType<typeof setInterval> | null = null;

function nextHero() {
    if (props.heroMovies.length <= 1) return;
    activeIndex.value = (activeIndex.value + 1) % props.heroMovies.length;
}
function setHero(i: number) {
    activeIndex.value = i;
    // restart timer agar user yang manual klik tidak langsung di-override
    if (rotateTimer) clearInterval(rotateTimer);
    if (props.heroMovies.length > 1) {
        rotateTimer = setInterval(nextHero, 6000);
    }
}

onMounted(() => {
    if (props.heroMovies.length > 1) {
        rotateTimer = setInterval(nextHero, 6000);
    }
});
onBeforeUnmount(() => {
    if (rotateTimer) clearInterval(rotateTimer);
});

function formatDate(iso: string | null): string {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
}

function bodyExcerpt(body: string, max = 120): string {
    return body.length > max ? body.slice(0, max).trimEnd() + '…' : body;
}
</script>

<template>
    <Head title="Home" />

    <!-- Hero section -->
    <section v-if="activeHero" class="relative h-[80vh] overflow-hidden">
        <HeroBackdrop :image="activeHero.backdrop ?? activeHero.poster" height="80vh" :blur-fallback="!activeHero.backdrop" />

        <!-- Content overlay -->
        <div class="absolute inset-x-0 bottom-0 z-10 mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 pb-16">
            <div class="max-w-2xl space-y-4">
                <p class="text-xs uppercase tracking-widest text-[var(--cinema-teal)]">Featured</p>
                <h1 class="font-display text-5xl md:text-7xl tracking-wide leading-none drop-shadow-lg">
                    {{ activeHero.title }}
                </h1>
                <p class="text-sm text-[var(--cinema-muted)]">
                    <span v-if="activeHero.year">{{ activeHero.year }}</span>
                    <span v-if="activeHero.duration_min"> · {{ activeHero.duration_min }} min</span>
                </p>
                <p class="text-base text-[var(--cinema-text)]/90 line-clamp-3">{{ activeHero.synopsis }}</p>

                <div class="flex items-center gap-4 mt-2">
                    <ScoreBadge :score="activeHero.avg_score" size="lg" />
                    <div class="text-xs text-[var(--cinema-muted)]">
                        <p>USER SCORE</p>
                        <p class="text-[var(--cinema-text)]">{{ activeHero.review_count }} reviews</p>
                    </div>
                </div>

                <div class="pt-4">
                    <Link
                        :href="`/movies/${activeHero.slug}`"
                        class="inline-block rounded-md bg-[var(--cinema-teal)] px-6 py-3 text-sm font-semibold text-[var(--cinema-base)] hover:opacity-90"
                    >
                        Lihat Detail →
                    </Link>
                </div>
            </div>

            <!-- Dot indicators -->
            <div v-if="heroMovies.length > 1" class="flex gap-2 mt-8">
                <button
                    v-for="(m, i) in heroMovies"
                    :key="m.id"
                    type="button"
                    @click="setHero(i)"
                    :class="[
                        'h-1 transition-all rounded-full',
                        i === activeIndex
                            ? 'w-8 bg-[var(--cinema-teal)]'
                            : 'w-4 bg-white/30 hover:bg-white/50',
                    ]"
                    :aria-label="`Lihat ${m.title}`"
                />
            </div>
        </div>
    </section>

    <!-- Empty state if no hero films -->
    <section v-else class="relative min-h-[60vh] flex items-center justify-center bg-gradient-to-br from-[var(--cinema-surface)] to-[var(--cinema-base)]">
        <div class="text-center max-w-xl px-4">
            <h1 class="font-display text-6xl tracking-wide">MOVIE REVIEW</h1>
            <p class="text-[var(--cinema-muted)] mt-3">
                Belum ada film unggulan dengan backdrop. Tambahkan film + backdrop di admin untuk mengisi hero rotator.
            </p>
            <Link href="/movies" class="mt-6 inline-block rounded-md bg-[var(--cinema-teal)] px-6 py-3 text-sm font-semibold text-[var(--cinema-base)]">
                Jelajahi Films →
            </Link>
        </div>
    </section>

    <!-- Popular this week -->
    <section v-if="popularThisWeek.length" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16">
        <div class="flex items-end justify-between mb-6">
            <h2 class="font-display text-3xl tracking-wide">POPULAR THIS WEEK</h2>
            <Link href="/movies?sort=reviews" class="text-sm text-[var(--cinema-muted)] hover:text-[var(--cinema-teal)]">
                Lihat semua →
            </Link>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
            <MovieCard v-for="m in popularThisWeek" :key="m.id" :movie="m" />
        </div>
    </section>

    <!-- Recently reviewed activity feed -->
    <section v-if="recentReviews.length" class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-16">
        <div class="flex items-end justify-between mb-6">
            <h2 class="font-display text-3xl tracking-wide">RECENTLY REVIEWED</h2>
        </div>
        <div class="space-y-4">
            <article
                v-for="r in recentReviews"
                :key="r.id"
                class="flex gap-4 p-4 rounded-xl border border-[var(--cinema-border)] bg-[var(--cinema-surface)] hover:bg-[var(--cinema-elevated)] transition-colors"
            >
                <Link :href="`/movies/${r.movie.slug}`" class="shrink-0">
                    <div class="h-24 w-16 rounded overflow-hidden bg-[var(--cinema-elevated)]">
                        <img v-if="r.movie.poster" :src="r.movie.poster" :alt="r.movie.title" class="h-full w-full object-cover" />
                    </div>
                </Link>
                <div class="flex-1 min-w-0">
                    <div class="flex items-start gap-3 mb-2">
                        <ScoreBadge :score="r.rating" size="sm" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm">
                                <span class="font-medium">{{ r.user.name }}</span>
                                <span class="text-[var(--cinema-muted)]"> mengulas </span>
                                <Link :href="`/movies/${r.movie.slug}`" class="font-display tracking-wide hover:text-[var(--cinema-teal)]">
                                    {{ r.movie.title }}
                                </Link>
                            </p>
                            <p class="text-xs text-[var(--cinema-muted)]">{{ formatDate(r.created_at) }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-[var(--cinema-text)]/85 line-clamp-2">{{ bodyExcerpt(r.body) }}</p>
                </div>
            </article>
        </div>
    </section>
</template>
