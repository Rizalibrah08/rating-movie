<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Movie {
    id: number;
    title: string;
    slug: string;
    year: number | null;
    poster: string | null;
    has_backdrop: boolean;
    published_count: number;
    pending_count: number;
    genres: Array<{ id: number; name: string }>;
}

interface Pagination {
    data: Movie[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
    total: number;
}

const props = defineProps<{
    movies: Pagination;
    filters: { q: string };
}>();

const search = ref(props.filters.q);
let debounceTimer: ReturnType<typeof setTimeout> | null = null;

watch(search, (val) => {
    if (debounceTimer) {
clearTimeout(debounceTimer);
}

    debounceTimer = setTimeout(() => {
        router.get('/admin/movies', { q: val || undefined }, { preserveState: true, preserveScroll: true, replace: true });
    }, 350);
});

const deleteForm = useForm({});
const confirmDeleteId = ref<number | null>(null);

function confirmDelete(id: number) {
    confirmDeleteId.value = id;
}

function cancelDelete() {
    confirmDeleteId.value = null;
}

function performDelete(id: number) {
    deleteForm.delete(`/admin/movies/${id}`, {
        preserveScroll: true,
        onFinish: () => {
            confirmDeleteId.value = null;
        },
    });
}
</script>

<template>
    <Head title="Admin · Films" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="font-display text-3xl tracking-wide">FILMS</h1>
            <Link
                href="/admin/movies/create"
                class="rounded-md bg-[var(--cinema-teal)] px-4 py-2 text-sm font-medium text-[var(--cinema-base)] hover:opacity-90 transition-opacity"
            >
                + Tambah Film
            </Link>
        </div>

        <div>
            <input
                v-model="search"
                type="search"
                placeholder="Cari judul…"
                class="w-full md:w-80 rounded-md bg-[var(--cinema-surface)] border border-[var(--cinema-border)] px-4 py-2 text-sm placeholder:text-[var(--cinema-muted)] focus:outline-none focus:ring-2 focus:ring-[var(--cinema-teal)]"
            />
        </div>

        <div class="overflow-x-auto rounded-lg border border-[var(--cinema-border)]">
            <table class="w-full text-sm">
                <thead class="bg-[var(--cinema-surface)] text-left text-[var(--cinema-muted)] uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-4 py-3 w-16">Poster</th>
                        <th class="px-4 py-3">Judul</th>
                        <th class="px-4 py-3">Tahun</th>
                        <th class="px-4 py-3">Genre</th>
                        <th class="px-4 py-3">Backdrop</th>
                        <th class="px-4 py-3 text-right">Reviews</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="m in movies.data"
                        :key="m.id"
                        class="border-t border-[var(--cinema-border)] hover:bg-[var(--cinema-surface)]/40"
                    >
                        <td class="px-4 py-3">
                            <div class="h-12 w-9 rounded overflow-hidden bg-[var(--cinema-elevated)]">
                                <img v-if="m.poster" :src="m.poster" :alt="m.title" class="h-full w-full object-cover" />
                            </div>
                        </td>
                        <td class="px-4 py-3 font-medium">{{ m.title }}</td>
                        <td class="px-4 py-3 text-[var(--cinema-muted)]">{{ m.year ?? '—' }}</td>
                        <td class="px-4 py-3 text-[var(--cinema-muted)]">
                            <span v-if="!m.genres.length">—</span>
                            <span v-else class="inline-flex flex-wrap gap-1">
                                <span
                                    v-for="g in m.genres"
                                    :key="g.id"
                                    class="rounded-full bg-[var(--cinema-elevated)] px-2 py-0.5 text-xs"
                                >{{ g.name }}</span>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span
                                v-if="m.has_backdrop"
                                class="inline-flex items-center rounded-full bg-[var(--score-green)]/15 text-[var(--score-green)] px-2 py-0.5 text-xs"
                            >ada</span>
                            <span
                                v-else
                                class="inline-flex items-center rounded-full bg-[var(--cinema-elevated)] text-[var(--cinema-muted)] px-2 py-0.5 text-xs"
                            >tidak</span>
                        </td>
                        <td class="px-4 py-3 text-right text-[var(--cinema-muted)]">
                            <span class="text-[var(--cinema-text)]">{{ m.published_count }}</span>
                            <span v-if="m.pending_count" class="text-[var(--score-yellow)]"> + {{ m.pending_count }} pending</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex gap-2">
                                <Link
                                    :href="`/movies/${m.slug}`"
                                    class="text-xs text-[var(--cinema-muted)] hover:text-[var(--cinema-text)]"
                                >Lihat</Link>
                                <Link
                                    :href="`/admin/movies/${m.id}/edit`"
                                    class="text-xs text-[var(--cinema-teal)] hover:opacity-80"
                                >Edit</Link>
                                <template v-if="confirmDeleteId === m.id">
                                    <button
                                        type="button"
                                        @click="performDelete(m.id)"
                                        :disabled="deleteForm.processing"
                                        class="text-xs text-[var(--score-red)] font-medium"
                                    >Konfirmasi</button>
                                    <button
                                        type="button"
                                        @click="cancelDelete"
                                        class="text-xs text-[var(--cinema-muted)]"
                                    >Batal</button>
                                </template>
                                <button
                                    v-else
                                    type="button"
                                    @click="confirmDelete(m.id)"
                                    class="text-xs text-[var(--score-red)] hover:opacity-80"
                                >Hapus</button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!movies.data.length">
                        <td colspan="7" class="px-4 py-12 text-center text-[var(--cinema-muted)]">Belum ada film.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav v-if="movies.last_page > 1" class="flex justify-center gap-1 flex-wrap" aria-label="Pagination">
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
