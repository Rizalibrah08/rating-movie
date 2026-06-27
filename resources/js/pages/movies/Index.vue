<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import MovieCard from '@/components/cinema/MovieCard.vue';

interface Movie {
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

interface PaginatedMovies {
    data: Movie[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
    total: number;
}

interface Genre {
    id: number;
    name: string;
    slug: string;
}

interface Filters {
    q: string;
    genre: string;
    year: number | null;
    sort: 'newest' | 'score' | 'reviews';
}

const props = defineProps<{
    movies: PaginatedMovies;
    filters: Filters;
    genres: Genre[];
}>();

const search = ref(props.filters.q);
const selectedGenre = ref(props.filters.genre);
const selectedYear = ref<number | null>(props.filters.year);
const sort = ref<Filters['sort']>(props.filters.sort);

const yearOptions = computed(() => {
    const now = new Date().getFullYear();
    const arr: number[] = [];

    for (let y = now; y >= 1990; y--) {
arr.push(y);
}

    return arr;
});

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

function applyFilters() {
    router.get(
        '/movies',
        {
            q: search.value || undefined,
            genre: selectedGenre.value || undefined,
            year: selectedYear.value || undefined,
            sort: sort.value !== 'newest' ? sort.value : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

watch(search, () => {
    if (debounceTimer) {
clearTimeout(debounceTimer);
}

    debounceTimer = setTimeout(applyFilters, 350);
});

watch([selectedGenre, selectedYear, sort], () => applyFilters());
</script>

<template>
    <Head title="Films" />
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
        <header class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <div>
                <h1 class="font-display text-5xl tracking-wide">FILMS</h1>
                <p class="text-[var(--cinema-muted)] mt-1">
                    {{ movies.total }} film tersedia · jelajah, cari, dan beri ulasan.
                </p>
            </div>
        </header>

        <!-- Filter bar -->
        <div class="flex flex-col md:flex-row gap-3 mb-8">
            <input
                v-model="search"
                type="search"
                placeholder="Cari judul film…"
                class="flex-1 rounded-md bg-[var(--cinema-surface)] border border-[var(--cinema-border)] px-4 py-2 text-sm placeholder:text-[var(--cinema-muted)] focus:outline-none focus:ring-2 focus:ring-[var(--cinema-teal)]"
            />
            <select
                v-model="selectedGenre"
                class="rounded-md bg-[var(--cinema-surface)] border border-[var(--cinema-border)] px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--cinema-teal)]"
            >
                <option value="">Semua Genre</option>
                <option v-for="g in genres" :key="g.id" :value="g.slug">{{ g.name }}</option>
            </select>
            <select
                v-model="selectedYear"
                class="rounded-md bg-[var(--cinema-surface)] border border-[var(--cinema-border)] px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--cinema-teal)]"
            >
                <option :value="null">Semua Tahun</option>
                <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
            </select>
            <select
                v-model="sort"
                class="rounded-md bg-[var(--cinema-surface)] border border-[var(--cinema-border)] px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--cinema-teal)]"
            >
                <option value="newest">Terbaru</option>
                <option value="score">Skor Tertinggi</option>
                <option value="reviews">Paling Banyak Direview</option>
            </select>
        </div>

        <!-- Grid -->
        <div
            v-if="movies.data.length"
            class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6"
        >
            <MovieCard v-for="movie in movies.data" :key="movie.id" :movie="movie" />
        </div>
        <div v-else class="py-16 text-center text-[var(--cinema-muted)]">
            Tidak ada film yang cocok dengan filter ini.
        </div>

        <!-- Pagination -->
        <nav
            v-if="movies.last_page > 1"
            class="mt-12 flex justify-center gap-1 flex-wrap"
            aria-label="Pagination"
        >
            <Link
                v-for="link in movies.links"
                :key="link.label"
                :href="link.url ?? ''"
                preserve-scroll
                :class="[
                    'min-w-9 h-9 px-3 inline-flex items-center justify-center rounded-md text-sm border transition-colors',
                    link.active
                        ? 'bg-[var(--cinema-teal)] text-[var(--cinema-base)] border-transparent'
                        : 'border-[var(--cinema-border)] hover:bg-[var(--cinema-elevated)]',
                    !link.url && 'opacity-40 pointer-events-none',
                ]"
                v-html="link.label"
            />
        </nav>
    </div>
</template>
