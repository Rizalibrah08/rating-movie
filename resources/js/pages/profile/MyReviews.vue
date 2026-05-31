<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import ScoreBadge from '@/components/cinema/ScoreBadge.vue';

interface ReviewItem {
    id: number;
    rating: number;
    body: string;
    status: 'published' | 'pending' | 'rejected';
    created_at: string | null;
    movie: { id: number; title: string; slug: string; year: number | null; poster: string | null } | null;
}

interface Pagination<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    last_page: number;
    total: number;
}

defineProps<{ reviews: Pagination<ReviewItem> }>();

function formatDate(iso: string | null): string {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

const deleteForm = useForm({});
const confirmDeleteId = ref<number | null>(null);

function performDelete(id: number) {
    deleteForm.delete(`/my-reviews/${id}`, {
        preserveScroll: true,
        onFinish: () => (confirmDeleteId.value = null),
    });
}

function statusColor(s: string): string {
    return {
        published: 'bg-[var(--score-green)]/15 text-[var(--score-green)]',
        pending: 'bg-[var(--score-yellow)]/15 text-[var(--score-yellow)]',
        rejected: 'bg-[var(--score-red)]/15 text-[var(--score-red)]',
    }[s] ?? '';
}
function statusLabel(s: string): string {
    return { published: 'Tampil', pending: 'Menunggu moderasi', rejected: 'Ditolak' }[s] ?? s;
}
</script>

<template>
    <Head title="Ulasan saya" />

    <section class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-12">
        <header class="mb-6 flex items-end justify-between">
            <div>
                <h1 class="font-display text-3xl tracking-wide">ULASAN SAYA</h1>
                <p class="text-sm text-[var(--cinema-muted)] mt-1">{{ reviews.total }} ulasan total · termasuk yang belum lulus moderasi.</p>
            </div>
            <Link href="/movies" class="text-sm text-[var(--cinema-muted)] hover:text-[var(--cinema-teal)]">Cari film →</Link>
        </header>

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
                        <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs', statusColor(r.status)]">
                            {{ statusLabel(r.status) }}
                        </span>
                    </div>
                    <p class="text-sm text-[var(--cinema-text)]/85 line-clamp-3 whitespace-pre-line">{{ r.body }}</p>

                    <div class="mt-3 pt-3 border-t border-[var(--cinema-border)] flex justify-end gap-2">
                        <button
                            v-if="confirmDeleteId === r.id"
                            type="button"
                            @click="performDelete(r.id)"
                            :disabled="deleteForm.processing"
                            class="text-xs text-[var(--score-red)] font-medium"
                        >Konfirmasi hapus</button>
                        <button
                            v-else
                            type="button"
                            @click="confirmDeleteId = r.id"
                            class="text-xs text-[var(--score-red)]/80 hover:text-[var(--score-red)]"
                        >Hapus</button>
                        <button
                            v-if="confirmDeleteId === r.id"
                            type="button"
                            @click="confirmDeleteId = null"
                            class="text-xs text-[var(--cinema-muted)]"
                        >Batal</button>
                    </div>
                </div>
            </article>
        </div>
        <div v-else class="py-16 text-center text-[var(--cinema-muted)] border border-dashed border-[var(--cinema-border)] rounded-xl">
            Belum ada ulasan. <Link href="/movies" class="text-[var(--cinema-teal)] hover:underline">Mulai ulas film →</Link>
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
