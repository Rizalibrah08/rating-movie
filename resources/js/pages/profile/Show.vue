<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ScoreBadge from '@/components/cinema/ScoreBadge.vue';

interface User {
    id: number;
    name: string;
    role: string;
    member_since: string | null;
}

interface ReviewItem {
    id: number;
    rating: number;
    body: string;
    created_at: string | null;
    movie: { id: number; title: string; slug: string; year: number | null; poster: string | null } | null;
}

interface Pagination<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    last_page: number;
    total: number;
}

defineProps<{
    user: User;
    reviews: Pagination<ReviewItem>;
    stats: { total_reviews: number; avg_given: number | null };
}>();

function formatDate(iso: string | null): string {
    if (!iso) {
return '';
}

    return new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}
function memberSince(iso: string | null): string {
    if (!iso) {
return '';
}

    return new Date(iso).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
}
</script>

<template>
    <Head :title="user.name" />

    <!-- Header banner -->
    <section class="relative bg-gradient-to-br from-[var(--cinema-elevated)] via-[var(--cinema-surface)] to-[var(--cinema-base)] py-16">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 flex items-center gap-6">
            <div class="h-24 w-24 rounded-full bg-[var(--cinema-teal)] flex items-center justify-center font-display text-5xl text-[var(--cinema-base)]">
                {{ user.name.charAt(0).toUpperCase() }}
            </div>
            <div class="flex-1">
                <h1 class="font-display text-4xl tracking-wide">{{ user.name }}</h1>
                <p class="text-sm text-[var(--cinema-muted)] mt-1">
                    Member since {{ memberSince(user.member_since) }}
                    <span v-if="user.role === 'admin'" class="inline-block ml-2 rounded-full bg-[var(--cinema-teal)]/15 text-[var(--cinema-teal)] px-2 py-0.5 text-xs">Admin</span>
                </p>
            </div>
            <div class="text-right space-y-2">
                <div class="text-xs uppercase tracking-wider text-[var(--cinema-muted)]">Avg Score Given</div>
                <ScoreBadge :score="stats.avg_given" size="lg" />
            </div>
        </div>
    </section>

    <!-- Stats row -->
    <section class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-md border border-[var(--cinema-border)] bg-[var(--cinema-surface)] p-5">
                <p class="text-xs uppercase tracking-wider text-[var(--cinema-muted)]">Total Reviews</p>
                <p class="font-mono text-3xl mt-1">{{ stats.total_reviews }}</p>
            </div>
            <div class="rounded-md border border-[var(--cinema-border)] bg-[var(--cinema-surface)] p-5">
                <p class="text-xs uppercase tracking-wider text-[var(--cinema-muted)]">Average Score</p>
                <div class="mt-1 flex items-center gap-3">
                    <ScoreBadge :score="stats.avg_given" size="md" />
                    <span class="text-sm text-[var(--cinema-muted)]">dari ulasan published</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Reviews list -->
    <section class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 pb-16">
        <h2 class="font-display text-2xl tracking-wide mb-4">ULASAN</h2>

        <div v-if="reviews.data.length" class="space-y-4">
            <article
                v-for="r in reviews.data"
                :key="r.id"
                class="flex gap-4 p-4 rounded-xl border border-[var(--cinema-border)] bg-[var(--cinema-surface)]"
            >
                <Link v-if="r.movie" :href="`/movies/${r.movie.slug}`" class="shrink-0">
                    <div class="h-24 w-16 rounded overflow-hidden bg-[var(--cinema-elevated)]">
                        <img v-if="r.movie.poster" :src="r.movie.poster" :alt="r.movie.title" class="h-full w-full object-cover" />
                    </div>
                </Link>
                <div class="flex-1 min-w-0">
                    <div class="flex items-start gap-3 mb-2">
                        <ScoreBadge :score="r.rating" size="sm" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm">
                                <Link v-if="r.movie" :href="`/movies/${r.movie.slug}`" class="font-display tracking-wide hover:text-[var(--cinema-teal)]">{{ r.movie.title }}</Link>
                                <span v-if="r.movie?.year" class="text-[var(--cinema-muted)] text-xs"> · {{ r.movie.year }}</span>
                            </p>
                            <p class="text-xs text-[var(--cinema-muted)]">{{ formatDate(r.created_at) }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-[var(--cinema-text)]/85 line-clamp-3 whitespace-pre-line">{{ r.body }}</p>
                </div>
            </article>
        </div>
        <div v-else class="py-12 text-center text-[var(--cinema-muted)] border border-dashed border-[var(--cinema-border)] rounded-xl">
            Belum ada ulasan published.
        </div>

        <nav v-if="reviews.last_page > 1" class="mt-8 flex justify-center gap-1 flex-wrap" aria-label="Pagination">
            <Link
                v-for="link in reviews.links"
                :key="link.label"
                :href="link.url ?? ''"
                preserve-scroll
                :class="[
                    'min-w-9 h-9 px-3 inline-flex items-center justify-center rounded-md text-sm border',
                    link.active ? 'bg-[var(--cinema-teal)] text-[var(--cinema-base)] border-transparent'
                                : 'border-[var(--cinema-border)] hover:bg-[var(--cinema-elevated)]',
                    !link.url && 'opacity-40 pointer-events-none',
                ]"
                v-html="link.label"
            />
        </nav>
    </section>
</template>
