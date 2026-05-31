<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import ScoreBadge from '@/components/cinema/ScoreBadge.vue';

interface Report {
    id: number;
    reason: string;
    note: string | null;
    status: 'pending' | 'resolved_hide' | 'resolved_keep';
    created_at: string | null;
    reporter: { id: number; name: string; email: string } | null;
    review: {
        id: number;
        rating: number;
        body: string;
        status: string;
        author: { id: number; name: string } | null;
        movie: { id: number; title: string; slug: string; poster: string | null } | null;
    } | null;
}

interface Pagination {
    data: Report[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    last_page: number;
    total: number;
}

defineProps<{ reports: Pagination }>();

function formatDate(iso: string | null): string {
    if (!iso) return '';
    return new Date(iso).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
}

const hideForm = useForm({});
const dismissForm = useForm({});

function hide(id: number) {
    if (!confirm('Sembunyikan ulasan ini? Status akan menjadi rejected.')) return;
    hideForm.post(`/admin/reports/${id}/hide`, { preserveScroll: true });
}
function dismiss(id: number) {
    dismissForm.post(`/admin/reports/${id}/dismiss`, { preserveScroll: true });
}

function statusLabel(s: string): string {
    return { pending: 'Pending', resolved_hide: 'Disembunyikan', resolved_keep: 'Tetap dipertahankan' }[s] ?? s;
}
function statusColor(s: string): string {
    return {
        pending: 'bg-[var(--score-yellow)]/15 text-[var(--score-yellow)]',
        resolved_hide: 'bg-[var(--score-red)]/15 text-[var(--score-red)]',
        resolved_keep: 'bg-[var(--cinema-elevated)] text-[var(--cinema-muted)]',
    }[s] ?? 'bg-[var(--cinema-elevated)] text-[var(--cinema-muted)]';
}
</script>

<template>
    <Head title="Admin · Reports" />
    <div class="space-y-6">
        <h1 class="font-display text-3xl tracking-wide">REPORTS</h1>
        <p class="text-sm text-[var(--cinema-muted)]">{{ reports.total }} laporan total · pending muncul di atas.</p>

        <div v-if="reports.data.length" class="space-y-4">
            <article
                v-for="r in reports.data"
                :key="r.id"
                class="rounded-xl border border-[var(--cinema-border)] bg-[var(--cinema-surface)] p-5"
            >
                <header class="flex items-start gap-3 mb-3">
                    <div :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs', statusColor(r.status)]">
                        {{ statusLabel(r.status) }}
                    </div>
                    <div class="text-xs text-[var(--cinema-muted)]">
                        Alasan: <span class="text-[var(--cinema-text)] font-medium">{{ r.reason }}</span> · {{ formatDate(r.created_at) }}
                    </div>
                </header>

                <p class="text-xs text-[var(--cinema-muted)] mb-3">
                    Dilaporkan oleh
                    <span class="text-[var(--cinema-text)]">{{ r.reporter?.name ?? '(akun terhapus)' }}</span>
                    <span v-if="r.reporter">· {{ r.reporter.email }}</span>
                    <span v-if="r.note">· "{{ r.note }}"</span>
                </p>

                <!-- Reviewed item -->
                <div v-if="r.review" class="rounded-md bg-[var(--cinema-elevated)] p-4 flex gap-3">
                    <Link v-if="r.review.movie" :href="`/movies/${r.review.movie.slug}`" class="shrink-0">
                        <div class="h-16 w-12 rounded overflow-hidden bg-[var(--cinema-base)]">
                            <img v-if="r.review.movie.poster" :src="r.review.movie.poster" :alt="r.review.movie.title" class="h-full w-full object-cover" />
                        </div>
                    </Link>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <ScoreBadge :score="r.review.rating" size="sm" />
                            <span class="text-sm">
                                <span class="font-medium">{{ r.review.author?.name ?? '(akun terhapus)' }}</span>
                                <span class="text-[var(--cinema-muted)]"> di </span>
                                <Link v-if="r.review.movie" :href="`/movies/${r.review.movie.slug}`" class="font-display tracking-wide hover:text-[var(--cinema-teal)]">
                                    {{ r.review.movie.title }}
                                </Link>
                            </span>
                            <span :class="['text-xs ml-auto rounded-full px-2 py-0.5', statusColor(r.review.status)]">{{ r.review.status }}</span>
                        </div>
                        <p class="text-sm text-[var(--cinema-text)]/85 line-clamp-3 whitespace-pre-line">{{ r.review.body }}</p>
                    </div>
                </div>
                <div v-else class="text-xs text-[var(--cinema-muted)] italic">Ulasan terkait sudah dihapus.</div>

                <div v-if="r.status === 'pending'" class="flex justify-end gap-2 mt-4 pt-3 border-t border-[var(--cinema-border)]">
                    <button
                        type="button"
                        @click="dismiss(r.id)"
                        :disabled="dismissForm.processing"
                        class="rounded-md border border-[var(--cinema-border)] px-3 py-1.5 text-xs hover:bg-[var(--cinema-elevated)]"
                    >Tolak laporan (keep)</button>
                    <button
                        type="button"
                        @click="hide(r.id)"
                        :disabled="hideForm.processing"
                        class="rounded-md bg-[var(--score-red)]/90 text-white px-3 py-1.5 text-xs hover:opacity-90"
                    >Setujui (hide review)</button>
                </div>
            </article>
        </div>
        <div v-else class="py-16 text-center text-[var(--cinema-muted)] border border-dashed border-[var(--cinema-border)] rounded-xl">
            Tidak ada laporan saat ini.
        </div>

        <nav v-if="reports.last_page > 1" class="flex justify-center gap-1 flex-wrap" aria-label="Pagination">
            <Link
                v-for="link in reports.links"
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
    </div>
</template>
