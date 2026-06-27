<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import MovieCard from '@/components/cinema/MovieCard.vue';
import ScoreBadge from '@/components/cinema/ScoreBadge.vue';

interface ColorDist {
    green: number;
    yellow: number;
    red: number;
}

interface RuleTrigger {
    rule: string;
    count: number;
}

interface TopMovie {
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

const props = defineProps<{
    totalMovies: number;
    totalUsers: number;
    totalAdmins: number;
    reviewsByStatus: { published: number; pending: number; rejected: number };
    totalReports: number;
    pendingReports: number;
    avgScore: number | null;
    colorDistribution: ColorDist;
    topMovies: TopMovie[];
    ruleTriggers: RuleTrigger[];
}>();

const totalReviews = computed(() => props.reviewsByStatus.published + props.reviewsByStatus.pending + props.reviewsByStatus.rejected);
const colorTotal = computed(() => props.colorDistribution.green + props.colorDistribution.yellow + props.colorDistribution.red || 1);

function pct(n: number): number {
    return Math.round((n / colorTotal.value) * 100);
}
</script>

<template>
    <Head title="Admin · Dashboard" />

    <div class="space-y-8">
        <h1 class="font-display text-3xl tracking-wide">DASHBOARD</h1>

        <!-- Top stat cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-md border border-[var(--cinema-border)] bg-[var(--cinema-surface)] p-5">
                <p class="text-xs uppercase tracking-wider text-[var(--cinema-muted)]">Films</p>
                <p class="font-mono text-3xl mt-1">{{ totalMovies }}</p>
            </div>
            <div class="rounded-md border border-[var(--cinema-border)] bg-[var(--cinema-surface)] p-5">
                <p class="text-xs uppercase tracking-wider text-[var(--cinema-muted)]">Members</p>
                <p class="font-mono text-3xl mt-1">{{ totalUsers }}</p>
                <p class="text-xs text-[var(--cinema-muted)] mt-1">+ {{ totalAdmins }} admin</p>
            </div>
            <div class="rounded-md border border-[var(--cinema-border)] bg-[var(--cinema-surface)] p-5">
                <p class="text-xs uppercase tracking-wider text-[var(--cinema-muted)]">Reviews</p>
                <p class="font-mono text-3xl mt-1">{{ totalReviews }}</p>
                <p class="text-xs text-[var(--cinema-muted)] mt-1 flex gap-2">
                    <span class="text-[var(--score-green)]">{{ reviewsByStatus.published }} pub</span>
                    <span class="text-[var(--score-yellow)]">{{ reviewsByStatus.pending }} pending</span>
                    <span class="text-[var(--score-red)]" :title="totalReviews ? Math.round((reviewsByStatus.rejected / totalReviews) * 100) + '% Rejection Rate' : ''">
                        {{ reviewsByStatus.rejected }} rej
                        <span v-if="totalReviews" class="opacity-70 ml-1">({{ Math.round((reviewsByStatus.rejected / totalReviews) * 100) }}%)</span>
                    </span>
                </p>
            </div>
            <div class="rounded-md border border-[var(--cinema-border)] bg-[var(--cinema-surface)] p-5">
                <p class="text-xs uppercase tracking-wider text-[var(--cinema-muted)]">Avg Score</p>
                <div class="mt-1 flex items-center gap-3">
                    <ScoreBadge :score="avgScore" size="md" />
                    <p v-if="avgScore !== null" class="text-xs text-[var(--cinema-muted)]">dari semua published</p>
                    <p v-else class="text-xs text-[var(--cinema-muted)]">belum ada data</p>
                </div>
            </div>
        </div>

        <!-- Color distribution histogram -->
        <section class="rounded-md border border-[var(--cinema-border)] bg-[var(--cinema-surface)] p-5">
            <h2 class="font-display text-xl tracking-wide mb-3">DISTRIBUSI SKOR</h2>
            <div class="flex h-3 rounded-full overflow-hidden bg-[var(--cinema-elevated)]">
                <div :style="{ width: pct(colorDistribution.green) + '%' }" class="bg-[var(--score-green)]" :title="colorDistribution.green + ' ulasan green'"></div>
                <div :style="{ width: pct(colorDistribution.yellow) + '%' }" class="bg-[var(--score-yellow)]" :title="colorDistribution.yellow + ' ulasan yellow'"></div>
                <div :style="{ width: pct(colorDistribution.red) + '%' }" class="bg-[var(--score-red)]" :title="colorDistribution.red + ' ulasan red'"></div>
            </div>
            <div class="grid grid-cols-3 gap-4 mt-3 text-sm font-mono">
                <div class="text-[var(--score-green)]">{{ colorDistribution.green }} <span class="text-[var(--cinema-muted)] text-xs">({{ pct(colorDistribution.green) }}%)</span> · 75–100</div>
                <div class="text-[var(--score-yellow)]">{{ colorDistribution.yellow }} <span class="text-[var(--cinema-muted)] text-xs">({{ pct(colorDistribution.yellow) }}%)</span> · 50–74</div>
                <div class="text-[var(--score-red)]">{{ colorDistribution.red }} <span class="text-[var(--cinema-muted)] text-xs">({{ pct(colorDistribution.red) }}%)</span> · 0–49</div>
            </div>
        </section>

        <!-- Top 5 + Rule triggers -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <section class="lg:col-span-2 rounded-md border border-[var(--cinema-border)] bg-[var(--cinema-surface)] p-5">
                <div class="flex items-end justify-between mb-3">
                    <h2 class="font-display text-xl tracking-wide">TOP 5 FILMS</h2>
                    <Link href="/admin/movies" class="text-xs text-[var(--cinema-muted)] hover:text-[var(--cinema-teal)]">Lihat semua →</Link>
                </div>
                <div v-if="topMovies.length" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                    <MovieCard v-for="m in topMovies" :key="m.id" :movie="m" />
                </div>
                <p v-else class="text-sm text-[var(--cinema-muted)]">Belum ada film dengan ≥ 2 ulasan published.</p>
            </section>

            <section class="rounded-md border border-[var(--cinema-border)] bg-[var(--cinema-surface)] p-5">
                <h2 class="font-display text-xl tracking-wide mb-3">FILTER TRIGGERS<br><span class="text-[10px] text-[var(--cinema-muted)] tracking-wider">30 hari terakhir</span></h2>
                <div v-if="ruleTriggers.length" class="space-y-2">
                    <div
                        v-for="t in ruleTriggers"
                        :key="t.rule"
                        class="flex items-center justify-between text-sm py-1.5 border-b border-[var(--cinema-border)] last:border-b-0"
                    >
                        <span class="text-[var(--cinema-text)] font-mono">{{ t.rule }}</span>
                        <span class="text-[var(--cinema-teal)] font-mono">{{ t.count }}</span>
                    </div>
                </div>
                <p v-else class="text-sm text-[var(--cinema-muted)]">Belum ada audit log dalam 30 hari terakhir.</p>
            </section>
        </div>

        <!-- Pending tasks -->
        <section class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Link
                href="/admin/moderation"
                class="rounded-md border border-[var(--score-yellow)]/30 bg-[var(--score-yellow)]/10 p-5 hover:bg-[var(--score-yellow)]/20 transition-colors"
            >
                <p class="text-xs uppercase tracking-wider text-[var(--score-yellow)]">Moderation Queue</p>
                <p class="font-mono text-2xl mt-1 text-[var(--cinema-text)]">{{ reviewsByStatus.pending }} <span class="text-sm text-[var(--cinema-muted)]">pending review</span></p>
            </Link>
            <Link
                href="/admin/reports"
                class="rounded-md border border-[var(--score-red)]/30 bg-[var(--score-red)]/10 p-5 hover:bg-[var(--score-red)]/20 transition-colors"
            >
                <p class="text-xs uppercase tracking-wider text-[var(--score-red)]">Reports</p>
                <p class="font-mono text-2xl mt-1 text-[var(--cinema-text)]">{{ pendingReports }} <span class="text-sm text-[var(--cinema-muted)]">/ {{ totalReports }} pending</span></p>
            </Link>
        </section>
    </div>
</template>
