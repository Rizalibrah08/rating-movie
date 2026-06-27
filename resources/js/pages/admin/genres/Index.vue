<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Genre {
    id: number;
    name: string;
    slug: string;
    movies_count: number;
}

defineProps<{ genres: Genre[] }>();

const createForm = useForm({ name: '' });

function submitCreate() {
    createForm.post('/admin/genres', {
        preserveScroll: true,
        onSuccess: () => createForm.reset('name'),
    });
}

const editingId = ref<number | null>(null);
const editName = ref('');
const editProcessing = ref(false);
const editError = ref<string | null>(null);

function startEdit(g: Genre) {
    editingId.value = g.id;
    editName.value = g.name;
    editError.value = null;
}

function cancelEdit() {
    editingId.value = null;
    editError.value = null;
}

function submitEdit() {
    if (!editingId.value) {
return;
}

    editProcessing.value = true;
    editError.value = null;
    const f = useForm({ name: editName.value });
    f.put(`/admin/genres/${editingId.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
        onError: (errs) => {
            editError.value = errs.name ?? 'Gagal menyimpan.';
        },
        onFinish: () => {
            editProcessing.value = false;
        },
    });
}

const deleteForm = useForm({});
const confirmDeleteId = ref<number | null>(null);

function performDelete(id: number) {
    deleteForm.delete(`/admin/genres/${id}`, {
        preserveScroll: true,
        onFinish: () => {
            confirmDeleteId.value = null;
        },
    });
}
</script>

<template>
    <Head title="Admin · Genres" />

    <div class="space-y-6">
        <h1 class="font-display text-3xl tracking-wide">GENRES</h1>

        <!-- Inline create -->
        <form @submit.prevent="submitCreate" class="flex gap-2 max-w-md">
            <input
                v-model="createForm.name"
                type="text"
                placeholder="Nama genre baru"
                class="flex-1 rounded-md bg-[var(--cinema-surface)] border border-[var(--cinema-border)] px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--cinema-teal)]"
            />
            <button
                type="submit"
                :disabled="createForm.processing"
                class="rounded-md bg-[var(--cinema-teal)] px-4 py-2 text-sm font-medium text-[var(--cinema-base)] hover:opacity-90 disabled:opacity-50"
            >Tambah</button>
        </form>
        <p v-if="createForm.errors.name" class="text-xs text-[var(--score-red)]">{{ createForm.errors.name }}</p>

        <div class="overflow-x-auto rounded-lg border border-[var(--cinema-border)]">
            <table class="w-full text-sm">
                <thead class="bg-[var(--cinema-surface)] text-left text-[var(--cinema-muted)] uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3 text-right">Jumlah Film</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="g in genres"
                        :key="g.id"
                        class="border-t border-[var(--cinema-border)] hover:bg-[var(--cinema-surface)]/40"
                    >
                        <td class="px-4 py-3">
                            <template v-if="editingId === g.id">
                                <input
                                    v-model="editName"
                                    @keyup.enter="submitEdit"
                                    @keyup.escape="cancelEdit"
                                    class="rounded-md bg-[var(--cinema-elevated)] border border-[var(--cinema-border)] px-2 py-1 text-sm w-full"
                                />
                                <p v-if="editError" class="text-xs text-[var(--score-red)] mt-1">{{ editError }}</p>
                            </template>
                            <span v-else class="font-medium">{{ g.name }}</span>
                        </td>
                        <td class="px-4 py-3 text-[var(--cinema-muted)] font-mono text-xs">{{ g.slug }}</td>
                        <td class="px-4 py-3 text-right text-[var(--cinema-muted)]">{{ g.movies_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex gap-2">
                                <template v-if="editingId === g.id">
                                    <button
                                        type="button"
                                        @click="submitEdit"
                                        :disabled="editProcessing"
                                        class="text-xs text-[var(--cinema-teal)]"
                                    >Simpan</button>
                                    <button
                                        type="button"
                                        @click="cancelEdit"
                                        class="text-xs text-[var(--cinema-muted)]"
                                    >Batal</button>
                                </template>
                                <template v-else>
                                    <button
                                        type="button"
                                        @click="startEdit(g)"
                                        class="text-xs text-[var(--cinema-teal)] hover:opacity-80"
                                    >Edit</button>
                                    <button
                                        v-if="confirmDeleteId === g.id"
                                        type="button"
                                        @click="performDelete(g.id)"
                                        :disabled="deleteForm.processing"
                                        class="text-xs text-[var(--score-red)] font-medium"
                                    >Konfirmasi</button>
                                    <button
                                        v-else
                                        type="button"
                                        @click="confirmDeleteId = g.id"
                                        class="text-xs text-[var(--score-red)] hover:opacity-80"
                                    >Hapus</button>
                                </template>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!genres.length">
                        <td colspan="4" class="px-4 py-12 text-center text-[var(--cinema-muted)]">Belum ada genre.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
