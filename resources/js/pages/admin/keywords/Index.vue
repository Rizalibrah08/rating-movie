<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Keyword {
    id: number;
    keyword: string;
    category: string;
    is_active: boolean;
    created_at: string;
}

interface Pagination {
    data: Keyword[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
    total: number;
}

interface Filters {
    q: string;
    category: string;
    active: boolean | null;
}

interface Stats {
    [k: string]: { active: number; total: number };
}

const props = defineProps<{
    keywords: Pagination;
    filters: Filters;
    categories: string[];
    stats: Stats;
}>();

// Filter state (synced via debounced GET)
const search = ref(props.filters.q);
const selectedCategory = ref(props.filters.category);
const activeFilter = ref<'all' | 'active' | 'inactive'>(
    props.filters.active === null ? 'all' : props.filters.active ? 'active' : 'inactive',
);
let debounceTimer: ReturnType<typeof setTimeout> | null = null;

function applyFilters() {
    router.get(
        '/admin/keywords',
        {
            q: search.value || undefined,
            category: selectedCategory.value || undefined,
            active: activeFilter.value === 'all' ? undefined : activeFilter.value === 'active' ? '1' : '0',
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
watch(search, () => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 350);
});
watch([selectedCategory, activeFilter], () => applyFilters());

// Add new keyword form
const createForm = useForm({
    keyword: '',
    category: 'spam' as string,
    is_active: true as boolean,
});

function submitCreate() {
    createForm.post('/admin/keywords', {
        preserveScroll: true,
        onSuccess: () => createForm.reset('keyword'),
    });
}

// Inline edit
const editingId = ref<number | null>(null);
const editKeyword = ref('');
const editCategory = ref('');
const editActive = ref(true);
const editError = ref<string | null>(null);
const editProcessing = ref(false);

function startEdit(k: Keyword) {
    editingId.value = k.id;
    editKeyword.value = k.keyword;
    editCategory.value = k.category;
    editActive.value = k.is_active;
    editError.value = null;
}

function cancelEdit() {
    editingId.value = null;
    editError.value = null;
}

function submitEdit() {
    if (!editingId.value) return;
    editProcessing.value = true;
    editError.value = null;
    const f = useForm({
        keyword: editKeyword.value,
        category: editCategory.value,
        is_active: editActive.value,
    });
    f.put(`/admin/keywords/${editingId.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
        onError: (errs) => {
            editError.value = errs.keyword ?? errs.category ?? 'Gagal menyimpan.';
        },
        onFinish: () => {
            editProcessing.value = false;
        },
    });
}

const toggleProcessingId = ref<number | null>(null);

function toggleActive(k: Keyword) {
    toggleProcessingId.value = k.id;
    const f = useForm({
        keyword: k.keyword,
        category: k.category,
        is_active: !k.is_active,
    });
    f.put(`/admin/keywords/${k.id}`, {
        preserveScroll: true,
        onFinish: () => (toggleProcessingId.value = null),
    });
}

const deleteForm = useForm({});
const confirmDeleteId = ref<number | null>(null);
function performDelete(id: number) {
    deleteForm.delete(`/admin/keywords/${id}`, {
        preserveScroll: true,
        onFinish: () => (confirmDeleteId.value = null),
    });
}

function categoryColor(cat: string): string {
    return {
        spam: 'bg-[var(--score-red)]/15 text-[var(--score-red)]',
        promosi: 'bg-[var(--cinema-orange)]/15 text-[var(--cinema-orange)]',
        insult: 'bg-[var(--score-red)]/15 text-[var(--score-red)]',
        slang_negative: 'bg-[var(--score-yellow)]/15 text-[var(--score-yellow)]',
        other: 'bg-[var(--cinema-elevated)] text-[var(--cinema-muted)]',
    }[cat] ?? 'bg-[var(--cinema-elevated)] text-[var(--cinema-muted)]';
}

const totalActive = computed(() =>
    Object.values(props.stats).reduce((sum, s) => sum + s.active, 0),
);
const totalAll = computed(() =>
    Object.values(props.stats).reduce((sum, s) => sum + s.total, 0),
);
</script>

<template>
    <Head title="Admin · Blacklist Keywords" />

    <div class="space-y-6">
        <div class="flex items-end justify-between flex-wrap gap-4">
            <div>
                <h1 class="font-display text-3xl tracking-wide">BLACKLIST KEYWORDS</h1>
                <p class="text-sm text-[var(--cinema-muted)] mt-1">
                    {{ totalActive }} aktif dari {{ totalAll }} total · digunakan oleh filter pipeline saat user submit ulasan.
                </p>
            </div>
        </div>

        <!-- Stats per category -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <div
                v-for="cat in categories"
                :key="cat"
                class="rounded-md border border-[var(--cinema-border)] bg-[var(--cinema-surface)] px-4 py-3"
            >
                <p class="text-xs uppercase tracking-wider text-[var(--cinema-muted)]">{{ cat }}</p>
                <p class="font-mono text-xl mt-1">
                    <span class="text-[var(--cinema-text)]">{{ stats[cat]?.active ?? 0 }}</span>
                    <span class="text-[var(--cinema-muted)] text-sm"> / {{ stats[cat]?.total ?? 0 }}</span>
                </p>
            </div>
        </div>

        <!-- Add new keyword -->
        <form
            @submit.prevent="submitCreate"
            class="flex flex-wrap gap-2 items-start p-4 rounded-md border border-[var(--cinema-border)] bg-[var(--cinema-surface)]"
        >
            <input
                v-model="createForm.keyword"
                type="text"
                placeholder="Kata atau frase baru…"
                class="flex-1 min-w-[200px] rounded-md bg-[var(--cinema-elevated)] border border-[var(--cinema-border)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--cinema-teal)]"
            />
            <select
                v-model="createForm.category"
                class="rounded-md bg-[var(--cinema-elevated)] border border-[var(--cinema-border)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--cinema-teal)]"
            >
                <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
            </select>
            <label class="inline-flex items-center gap-2 text-sm">
                <input
                    v-model="createForm.is_active"
                    type="checkbox"
                    class="rounded border-[var(--cinema-border)] bg-[var(--cinema-elevated)]"
                />
                Aktif
            </label>
            <button
                type="submit"
                :disabled="createForm.processing"
                class="rounded-md bg-[var(--cinema-teal)] px-4 py-2 text-sm font-medium text-[var(--cinema-base)] hover:opacity-90 disabled:opacity-50"
            >+ Tambah</button>
        </form>
        <p v-if="createForm.errors.keyword" class="text-xs text-[var(--score-red)] -mt-3">{{ createForm.errors.keyword }}</p>
        <p v-if="createForm.errors.category" class="text-xs text-[var(--score-red)] -mt-3">{{ createForm.errors.category }}</p>

        <!-- Filter bar -->
        <div class="flex flex-wrap gap-2">
            <input
                v-model="search"
                type="search"
                placeholder="Cari kata kunci…"
                class="flex-1 min-w-[200px] max-w-md rounded-md bg-[var(--cinema-surface)] border border-[var(--cinema-border)] px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--cinema-teal)]"
            />
            <select
                v-model="selectedCategory"
                class="rounded-md bg-[var(--cinema-surface)] border border-[var(--cinema-border)] px-4 py-2 text-sm"
            >
                <option value="">Semua kategori</option>
                <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
            </select>
            <select
                v-model="activeFilter"
                class="rounded-md bg-[var(--cinema-surface)] border border-[var(--cinema-border)] px-4 py-2 text-sm"
            >
                <option value="all">Semua status</option>
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
            </select>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto rounded-lg border border-[var(--cinema-border)]">
            <table class="w-full text-sm">
                <thead class="bg-[var(--cinema-surface)] text-left text-[var(--cinema-muted)] uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Keyword</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="k in keywords.data"
                        :key="k.id"
                        class="border-t border-[var(--cinema-border)] hover:bg-[var(--cinema-surface)]/40"
                    >
                        <td class="px-4 py-3 font-mono">
                            <template v-if="editingId === k.id">
                                <input
                                    v-model="editKeyword"
                                    @keyup.enter="submitEdit"
                                    @keyup.escape="cancelEdit"
                                    class="rounded-md bg-[var(--cinema-elevated)] border border-[var(--cinema-border)] px-2 py-1 text-sm w-full"
                                />
                                <p v-if="editError" class="text-xs text-[var(--score-red)] mt-1">{{ editError }}</p>
                            </template>
                            <span v-else>{{ k.keyword }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <template v-if="editingId === k.id">
                                <select
                                    v-model="editCategory"
                                    class="rounded-md bg-[var(--cinema-elevated)] border border-[var(--cinema-border)] px-2 py-1 text-sm"
                                >
                                    <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                                </select>
                            </template>
                            <span v-else :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs', categoryColor(k.category)]">
                                {{ k.category }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <template v-if="editingId === k.id">
                                <label class="inline-flex items-center gap-2 text-xs">
                                    <input v-model="editActive" type="checkbox" class="rounded" />
                                    Aktif
                                </label>
                            </template>
                            <button
                                v-else
                                type="button"
                                @click="toggleActive(k)"
                                :disabled="toggleProcessingId === k.id"
                                :class="[
                                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs transition-colors',
                                    k.is_active
                                        ? 'bg-[var(--score-green)]/15 text-[var(--score-green)]'
                                        : 'bg-[var(--cinema-elevated)] text-[var(--cinema-muted)]',
                                ]"
                            >
                                {{ k.is_active ? 'aktif' : 'nonaktif' }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex gap-2">
                                <template v-if="editingId === k.id">
                                    <button
                                        type="button"
                                        @click="submitEdit"
                                        :disabled="editProcessing"
                                        class="text-xs text-[var(--cinema-teal)]"
                                    >Simpan</button>
                                    <button type="button" @click="cancelEdit" class="text-xs text-[var(--cinema-muted)]">Batal</button>
                                </template>
                                <template v-else>
                                    <button
                                        type="button"
                                        @click="startEdit(k)"
                                        class="text-xs text-[var(--cinema-teal)] hover:opacity-80"
                                    >Edit</button>
                                    <button
                                        v-if="confirmDeleteId === k.id"
                                        type="button"
                                        @click="performDelete(k.id)"
                                        :disabled="deleteForm.processing"
                                        class="text-xs text-[var(--score-red)] font-medium"
                                    >Konfirmasi</button>
                                    <button
                                        v-else
                                        type="button"
                                        @click="confirmDeleteId = k.id"
                                        class="text-xs text-[var(--score-red)] hover:opacity-80"
                                    >Hapus</button>
                                </template>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!keywords.data.length">
                        <td colspan="4" class="px-4 py-12 text-center text-[var(--cinema-muted)]">Belum ada kata kunci yang cocok dengan filter ini.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav v-if="keywords.last_page > 1" class="flex justify-center gap-1 flex-wrap" aria-label="Pagination">
            <Link
                v-for="link in keywords.links"
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
