<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import HeroBackdrop from '@/components/cinema/HeroBackdrop.vue';
import GlassPanel from '@/components/cinema/GlassPanel.vue';
import ScoreBadge from '@/components/cinema/ScoreBadge.vue';
import ReviewForm from '@/components/cinema/ReviewForm.vue';

interface Genre {
    id: number;
    name: string;
    slug: string;
}

interface Movie {
    id: number;
    title: string;
    slug: string;
    synopsis: string;
    year: number | null;
    duration_min: number | null;
    director: string | null;
    poster: string | null;
    backdrop: string | null;
    has_backdrop: boolean;
    genres: Genre[];
    avg_score: number | null;
    review_count: number;
}

interface Review {
    id: number;
    rating: number;
    body: string;
    created_at: string | null;
    user: { id: number; name: string };
}

interface PaginatedReviews {
    data: Review[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
    total: number;
}

interface BlockedKeyword {
    keyword: string;
    category: string;
}

interface UserReviewStatus {
    id: number;
    status: 'published' | 'pending' | 'rejected';
    rating: number;
}

const props = defineProps<{
    movie: Movie;
    reviews: PaginatedReviews;
    blockedKeywords: BlockedKeyword[];
    userReviewStatus: UserReviewStatus | null;
}>();

const page = usePage<any>();
const user = computed(() => page.props.auth?.user ?? null);
const heroImage = computed(() => props.movie.backdrop ?? props.movie.poster);

const showForm = ref(false);

function formatDate(iso: string | null): string {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

// Flash banner
const flashSuccess = ref<string | null>(null);
const flashError = ref<string | null>(null);
function pickFlash() {
    flashSuccess.value = page.props.flash?.success ?? null;
    flashError.value = page.props.flash?.error ?? null;
    if (flashSuccess.value || flashError.value) {
        setTimeout(() => {
            flashSuccess.value = null;
            flashError.value = null;
        }, 5000);
    }
}
watch(() => page.props.flash, () => pickFlash(), { immediate: true, deep: true });

const canReview = computed(() => user.value && !props.userReviewStatus);

// Login prompt modal (untuk guest)
const showLoginPrompt = ref(false);

// === Report ulasan state ===
const reportingReviewId = ref<number | null>(null);
const reportForm = useForm({
    reason: 'spam' as 'spam' | 'offensive' | 'misleading' | 'other',
    note: '',
});

function openReport(reviewId: number) {
    reportingReviewId.value = reviewId;
    reportForm.reset();
    reportForm.reason = 'spam';
}
function closeReport() {
    reportingReviewId.value = null;
}
function submitReport() {
    if (!reportingReviewId.value) return;
    reportForm.post(`/reviews/${reportingReviewId.value}/report`, {
        preserveScroll: true,
        onSuccess: () => closeReport(),
    });
}
</script>

<template>
    <Head :title="movie.title" />

    <article class="relative">
        <!-- Layer 1: backdrop fixed full-screen with parallax. Jika tidak ada backdrop,
             pakai poster sebagai fallback ambient (di-blur + scaled). -->
        <HeroBackdrop
            :image="heroImage"
            height="100vh"
            parallax
            fixed
            :blur-fallback="!movie.has_backdrop"
        />

        <!-- Layer 2: hero content (relative on top of backdrop) -->
        <section class="relative z-10 mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 pt-16 pb-32 min-h-[80vh] flex items-end">
            <div class="grid grid-cols-1 md:grid-cols-[300px_1fr] gap-8 w-full">
                <!-- Poster -->
                <div class="hidden md:block">
                    <div class="aspect-[2/3] overflow-hidden rounded-lg shadow-2xl ring-1 ring-white/10">
                        <img v-if="movie.poster" :src="movie.poster" :alt="movie.title" class="h-full w-full object-cover" />
                        <div v-else class="h-full w-full bg-[var(--cinema-elevated)] flex items-center justify-center text-[var(--cinema-muted)]">
                            No poster
                        </div>
                    </div>
                </div>

                <!-- Info -->
                <div class="flex flex-col gap-4">
                    <h1 class="font-display text-5xl md:text-6xl tracking-wide leading-tight drop-shadow-lg">
                        {{ movie.title }}
                    </h1>
                    <p class="text-sm text-[var(--cinema-muted)]">
                        <span v-if="movie.year">{{ movie.year }}</span>
                        <span v-if="movie.duration_min"> · {{ movie.duration_min }} min</span>
                        <span v-if="movie.director"> · Disutradarai oleh {{ movie.director }}</span>
                    </p>

                    <!-- Genre chips -->
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="g in movie.genres"
                            :key="g.id"
                            :href="`/movies?genre=${g.slug}`"
                            class="rounded-full border border-[var(--cinema-border)] bg-[var(--cinema-base)]/50 backdrop-blur-sm px-3 py-1 text-xs hover:border-[var(--cinema-teal)] transition-colors"
                        >
                            {{ g.name }}
                        </Link>
                    </div>

                    <!-- Synopsis -->
                    <p class="text-base text-[var(--cinema-text)]/90 max-w-2xl line-clamp-4 mt-2">
                        {{ movie.synopsis }}
                    </p>

                    <!-- 3 ScoreBadge -->
                    <div class="flex items-center gap-6 mt-4">
                        <div class="flex flex-col items-center gap-1">
                            <ScoreBadge :score="movie.avg_score" size="lg" />
                            <span class="text-xs uppercase tracking-wider text-[var(--cinema-muted)] mt-2">User Score</span>
                        </div>
                        <div class="flex flex-col items-center gap-1">
                            <div class="h-[72px] w-[72px] inline-flex items-center justify-center rounded-md bg-[var(--cinema-elevated)] border border-[var(--cinema-border)] font-mono font-bold text-[36px] text-[var(--cinema-text)]">
                                {{ movie.review_count }}
                            </div>
                            <span class="text-xs uppercase tracking-wider text-[var(--cinema-muted)] mt-2">Reviews</span>
                        </div>
                        <div class="flex flex-col items-center gap-1">
                            <div class="h-[72px] w-[72px] inline-flex items-center justify-center rounded-md bg-[var(--cinema-elevated)] border border-[var(--cinema-border)] font-mono font-bold text-[36px] text-[var(--cinema-teal)]">
                                ↑
                            </div>
                            <span class="text-xs uppercase tracking-wider text-[var(--cinema-muted)] mt-2">Trending</span>
                        </div>
                    </div>

                    <!-- CTA -->
                    <div class="flex gap-3 mt-6">
                        <button
                            v-if="canReview"
                            type="button"
                            @click="showForm = !showForm"
                            class="rounded-md bg-[var(--cinema-teal)] px-5 py-2.5 text-sm font-semibold text-[var(--cinema-base)] hover:opacity-90 transition-opacity"
                        >
                            {{ showForm ? 'Tutup Form' : 'Tulis Ulasan' }}
                        </button>
                        <button
                            v-else-if="!user"
                            type="button"
                            @click="showLoginPrompt = true"
                            class="rounded-md bg-[var(--cinema-teal)] px-5 py-2.5 text-sm font-semibold text-[var(--cinema-base)] hover:opacity-90 transition-opacity"
                        >
                            Tulis Ulasan
                        </button>
                        <div
                            v-else-if="userReviewStatus"
                            :class="[
                                'rounded-md px-4 py-2.5 text-sm border',
                                userReviewStatus.status === 'published'
                                    ? 'border-[var(--score-green)]/30 bg-[var(--score-green)]/10 text-[var(--score-green)]'
                                    : 'border-[var(--score-yellow)]/30 bg-[var(--score-yellow)]/10 text-[var(--score-yellow)]',
                            ]"
                        >
                            {{ userReviewStatus.status === 'published'
                                ? `✓ Kamu telah memberi skor ${userReviewStatus.rating}/100`
                                : `⏳ Ulasan kamu (${userReviewStatus.rating}/100) menunggu moderasi` }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Layer 3: Glass content panel naik -->
        <GlassPanel as="section" variant="strong" class="relative z-10 mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-12">

            <!-- Flash banners -->
            <div v-if="flashSuccess" class="mb-4 px-4 py-3 rounded-md border border-[var(--score-green)]/30 bg-[var(--score-green)]/10 text-[var(--score-green)] text-sm">
                {{ flashSuccess }}
            </div>
            <div v-if="flashError" class="mb-4 px-4 py-3 rounded-md border border-[var(--score-red)]/30 bg-[var(--score-red)]/10 text-[var(--score-red)] text-sm">
                {{ flashError }}
            </div>

            <!-- Form ulasan (saat user authorized & belum review & form di-toggle) -->
            <div
                v-if="canReview && showForm"
                class="mb-8 rounded-xl border border-white/10 bg-white/[0.03] backdrop-blur-md p-6"
            >
                <ReviewForm
                    :movie-id="movie.id"
                    :movie-slug="movie.slug"
                    :blocked-keywords="blockedKeywords"
                />
            </div>

            <!-- Prompt untuk guest di atas daftar ulasan -->
            <div
                v-if="!user"
                class="mb-6 flex items-center justify-between rounded-xl border border-[var(--cinema-border)] bg-[var(--cinema-surface)] px-5 py-4"
            >
                <p class="text-sm text-[var(--cinema-muted)]">Login untuk memberi rating dan menulis ulasan.</p>
                <button
                    type="button"
                    @click="showLoginPrompt = true"
                    class="rounded-md bg-[var(--cinema-teal)] px-4 py-2 text-sm font-semibold text-[var(--cinema-base)] hover:opacity-90 transition-opacity"
                >
                    Login
                </button>
            </div>

            <h2 class="font-display text-3xl tracking-wide mb-6">ULASAN ({{ reviews.total }})</h2>

            <div v-if="reviews.data.length" class="space-y-4">
                <article
                    v-for="r in reviews.data"
                    :key="r.id"
                    class="rounded-xl border border-white/10 bg-white/5 backdrop-blur-md p-5"
                >
                    <header class="flex items-center gap-3 mb-3">
                        <div class="h-9 w-9 rounded-full bg-[var(--cinema-elevated)] flex items-center justify-center text-sm font-medium">
                            {{ r.user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium">{{ r.user.name }}</p>
                            <p class="text-xs text-[var(--cinema-muted)]">{{ formatDate(r.created_at) }}</p>
                        </div>
                        <ScoreBadge :score="r.rating" size="md" />
                    </header>
                    <p class="text-sm leading-relaxed text-[var(--cinema-text)]/90 whitespace-pre-line">{{ r.body }}</p>
                    <!-- Report action (hanya tampil jika user login & bukan ulasan miliknya) -->
                    <footer
                        v-if="user && r.user.id !== user.id"
                        class="mt-3 pt-3 border-t border-white/10 flex justify-end"
                    >
                        <button
                            type="button"
                            @click="openReport(r.id)"
                            class="text-xs text-[var(--cinema-muted)] hover:text-[var(--score-red)] transition-colors"
                        >⚑ Laporkan</button>
                    </footer>
                </article>
            </div>
            <div v-else class="py-12 text-center text-[var(--cinema-muted)]">
                Belum ada ulasan untuk film ini.
            </div>

            <!-- Pagination -->
            <nav
                v-if="reviews.last_page > 1"
                class="mt-8 flex justify-center gap-1 flex-wrap"
                aria-label="Pagination"
            >
                <Link
                    v-for="link in reviews.links"
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
        </GlassPanel>

        <!-- Login prompt modal (untuk guest yang coba rating/komentar) -->
        <div
            v-if="showLoginPrompt"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4"
            @click.self="showLoginPrompt = false"
        >
            <div class="w-full max-w-sm rounded-xl border border-[var(--cinema-border)] bg-[var(--cinema-surface)] p-6 shadow-2xl text-center">
                <div class="mb-4 text-4xl">🎬</div>
                <h3 class="font-display text-2xl tracking-wide mb-2">LOGIN DULU</h3>
                <p class="text-sm text-[var(--cinema-muted)] mb-6">
                    Kamu perlu login untuk memberi rating dan menulis ulasan film ini.
                </p>
                <div class="flex gap-3 justify-center">
                    <button
                        type="button"
                        @click="showLoginPrompt = false"
                        class="rounded-md border border-[var(--cinema-border)] px-5 py-2.5 text-sm hover:bg-[var(--cinema-elevated)] transition-colors"
                    >
                        Kembali
                    </button>
                    <Link
                        href="/login"
                        class="rounded-md bg-[var(--cinema-teal)] px-5 py-2.5 text-sm font-semibold text-[var(--cinema-base)] hover:opacity-90 transition-opacity"
                    >
                        Login
                    </Link>
                </div>
            </div>
        </div>

        <!-- Report modal -->
        <div
            v-if="reportingReviewId !== null"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4"
            @click.self="closeReport"
        >
            <div class="w-full max-w-md rounded-xl border border-[var(--cinema-border)] bg-[var(--cinema-surface)] p-6 shadow-2xl">
                <h3 class="font-display text-2xl tracking-wide mb-3">LAPORKAN ULASAN</h3>
                <p class="text-xs text-[var(--cinema-muted)] mb-4">
                    Pilih alasan dan tambahkan catatan opsional. Tim moderasi akan meninjau laporan ini.
                </p>

                <form @submit.prevent="submitReport" class="space-y-4">
                    <div>
                        <label class="block text-xs uppercase tracking-wider text-[var(--cinema-muted)] mb-2">Alasan</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                v-for="r in (['spam','offensive','misleading','other'] as const)"
                                :key="r"
                                type="button"
                                @click="reportForm.reason = r"
                                :class="[
                                    'rounded-md border px-3 py-2 text-sm transition-colors',
                                    reportForm.reason === r
                                        ? 'bg-[var(--cinema-teal)] text-[var(--cinema-base)] border-transparent'
                                        : 'border-[var(--cinema-border)] hover:border-[var(--cinema-teal)]',
                                ]"
                            >{{ r === 'spam' ? 'Spam' : r === 'offensive' ? 'Tidak pantas' : r === 'misleading' ? 'Menyesatkan' : 'Lainnya' }}</button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs uppercase tracking-wider text-[var(--cinema-muted)] mb-2">Catatan (opsional)</label>
                        <textarea
                            v-model="reportForm.note"
                            rows="3"
                            maxlength="500"
                            placeholder="Jelaskan singkat alasan kenapa ulasan ini perlu ditinjau…"
                            class="w-full rounded-md bg-[var(--cinema-elevated)] border border-[var(--cinema-border)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--cinema-teal)] resize-none"
                        ></textarea>
                        <p v-if="reportForm.errors.note" class="text-xs text-[var(--score-red)] mt-1">{{ reportForm.errors.note }}</p>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="closeReport"
                            class="rounded-md border border-[var(--cinema-border)] px-4 py-2 text-sm hover:bg-[var(--cinema-elevated)]"
                        >Batal</button>
                        <button
                            type="submit"
                            :disabled="reportForm.processing"
                            class="rounded-md bg-[var(--score-red)]/90 text-white px-4 py-2 text-sm font-medium hover:opacity-90 disabled:opacity-50"
                        >Kirim Laporan</button>
                    </div>
                </form>
            </div>
        </div>
    </article>
</template>
