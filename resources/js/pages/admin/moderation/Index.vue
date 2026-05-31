<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import ScoreBadge from '@/components/cinema/ScoreBadge.vue';

interface PendingReview {
    id: number;
    rating: number;
    body: string;
    created_at: string | null;
    ip: string | null;
    user: { id: number; name: string; email: string } | null;
    movie: { id: number; title: string; slug: string; poster: string | null } | null;
    rule_triggered: string | null;
    reason: string | null;
}

interface Pagination {
    data: PendingReview[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
    total: number;
}

defineProps<{ reviews: Pagination }>();

function formatDate(iso: string | null): string {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
}

const approveForm = useForm({});
const rejectForm = useForm({});

function approve(id: number) {
    approveForm.post(`/admin/moderation/${id}/approve`, { preserveScroll: true });
}
function reject(id: number) {
    if (!confirm('Tolak ulasan ini? Status akan menjadi rejected dan tidak ditampilkan.')) return;
    rejectForm.post(`/admin/moderation/${id}/reject`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Admin · Moderation Queue" />

    <div class="space-y-6">
        <div>
            <h1 class="font-display text-3xl tracking-wide">MODERATION QUEUE</h1>
            <p class="text-sm text-[var(--cinema-muted)] mt-1">
                {{ reviews.total }} ulasan menunggu peninjauan. Approve untuk publish, atau reject untuk membuangnya.
            </p>
        </div>

        <div v-if="reviews.data.length" class="space-y-4">
            <article
                v-for="r in reviews.data"
                :key="r.id"
                class="rounded-xl border border-[var(--score-yellow)]/30 bg-[var(--cinema-surface)] p-5"
            >
                <header class="flex items-start gap-4 mb-4">
                    <Link v-if="r.movie" :href="`/movies/${r.movie.slug}`" class="shrink-0">
                        <div class="h-20 w-14 rounded overflow-hidden bg-[var(--cinema-elevated)]">
                            <img v-if="r.movie.poster" :src="r.movie.poster" :alt="r.movie.title" class="h-full w-full object-cover" />
                        </div>
                    </Link>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 mb-1">
                            <ScoreBadge :score="r.rating" size="md" />
                            <div class="text-sm">
                                <p>
                                    <span class="font-medium">{{ r.user?.name ?? '(akun terhapus)' }}</span>
                                    <span v-if="r.user" class="text-[var(--cinema-muted)] text-xs"> · {{ r.user.email }}</span>
                                </p>
                                <p class="text-xs text-[var(--cinema-muted)]">
                                    Untuk
                                    <Link v-if="r.movie" :href="`/movies/${r.movie.slug}`" class="hover:text-[var(--cinema-teal)]">
                                        {{ r.movie.title }}
                                    </Link>
                                    · {{ formatDate(r.created_at) }}
                                    <span v-if="r.ip"> · {{ r.ip }}</span>
                                </p>
                            </div>
                        </div>
                        <div v-if="r.rule_triggered" class="inline-flex items-center gap-2 rounded-full bg-[var(--score-yellow)]/15 text-[var(--score-yellow)] px-2 py-0.5 text-xs">
                            <span>⚠ {{ r.rule_triggered }}</span>
                            <span v-if="r.reason" class="text-[var(--cinema-muted)]">· {{ r.reason }}</span>
                        </div>
                    </div>
                </header>

                <p class="text-sm leading-relaxed text-[var(--cinema-text)]/90 whitespace-pre-line p-4 rounded-md bg-[var(--cinema-elevated)]">{{ r.body }}</p>

                <div class="flex items-center justify-end gap-2 mt-4 pt-4 border-t border-[var(--cinema-border)]">
                    <button
                        type="button"
                        @click="reject(r.id)"
                        :disabled="rejectForm.processing"
                        class="rounded-md border border-[var(--score-red)]/40 text-[var(--score-red)] px-4 py-2 text-sm hover:bg-[var(--score-red)]/10"
                    >Reject</button>
                    <button
                        type="button"
                        @click="approve(r.id)"
                        :disabled="approveForm.processing"
                        class="rounded-md bg-[var(--score-green)] text-[var(--cinema-base)] px-4 py-2 text-sm font-medium hover:opacity-90"
                    >Approve</button>
                </div>
            </article>
        </div>
        <div v-else class="py-16 text-center text-[var(--cinema-muted)] border border-dashed border-[var(--cinema-border)] rounded-xl">
            🎉 Tidak ada ulasan pending. Semua sudah di-moderasi.
        </div>

        <nav v-if="reviews.last_page > 1" class="flex justify-center gap-1 flex-wrap" aria-label="Pagination">
            <Link
                v-for="link in reviews.links"
                :key="link.label"
                :href="link.url ?? ''"
                preserve-scroll
                :class="[
                    'min-w-9 h-9 px-3 inline-flex items-center justify-center rounded-md text-sm border',
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
