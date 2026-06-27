<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    trust_score: number;
    banned_at: string | null;
    created_at: string;
}

interface Pagination {
    data: User[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
    total: number;
}

defineProps<{
    users: Pagination;
}>();

const actionForm = useForm({});
const processingId = ref<number | null>(null);

function toggleBan(user: User) {
    if (user.role === 'admin') {
        alert('Cannot ban an admin.');

        return;
    }
    
    processingId.value = user.id;

    if (user.banned_at) {
        actionForm.post(`/admin/users/${user.id}/unban`, {
            preserveScroll: true,
            onFinish: () => (processingId.value = null),
        });
    } else {
        if (confirm(`Apakah Anda yakin ingin memblokir ${user.name}?`)) {
            actionForm.post(`/admin/users/${user.id}/ban`, {
                preserveScroll: true,
                onFinish: () => (processingId.value = null),
            });
        } else {
            processingId.value = null;
        }
    }
}

function toggleRole(user: User) {
    processingId.value = user.id;
    const newRole = user.role === 'admin' ? 'user' : 'admin';

    if (confirm(`Ubah role ${user.name} menjadi ${newRole.toUpperCase()}?`)) {
        actionForm.patch(`/admin/users/${user.id}/role`, {
            data: { role: newRole },
            preserveScroll: true,
            onFinish: () => (processingId.value = null),
        });
    } else {
        processingId.value = null;
    }
}

function updateTrustScorePrompt(user: User) {
    const val = prompt(`Masukkan Trust Score baru untuk ${user.name} (saat ini: ${user.trust_score}):`, user.trust_score.toString());
    if (val === null) return;
    
    const num = parseInt(val, 10);
    if (isNaN(num) || num < -1000 || num > 1000) {
        alert('Trust Score harus berupa angka antara -1000 dan 1000.');
        return;
    }

    processingId.value = user.id;
    actionForm.patch(`/admin/users/${user.id}/trust-score`, {
        data: { trust_score: num },
        preserveScroll: true,
        onFinish: () => (processingId.value = null),
    });
}
</script>

<template>
    <Head title="Admin · Users Management" />

    <div class="space-y-6">
        <div class="flex items-end justify-between flex-wrap gap-4">
            <div>
                <h1 class="font-display text-3xl tracking-wide">USERS MANAGEMENT</h1>
                <p class="text-sm text-[var(--cinema-muted)] mt-1">
                    Kelola pengguna, ubah role, dan tangani akun yang bermasalah.
                </p>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-[var(--cinema-border)]">
            <table class="w-full text-sm">
                <thead class="bg-[var(--cinema-surface)] text-left text-[var(--cinema-muted)] uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Pengguna</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3 text-center">Trust Score</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="user in users.data"
                        :key="user.id"
                        class="border-t border-[var(--cinema-border)] hover:bg-[var(--cinema-surface)]/40"
                    >
                        <td class="px-4 py-3">
                            <div class="font-medium text-[var(--cinema-text)]">{{ user.name }}</div>
                            <div class="text-xs text-[var(--cinema-muted)]">{{ user.email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span :class="[
                                'inline-flex items-center rounded-full px-2 py-0.5 text-xs',
                                user.role === 'admin' ? 'bg-[var(--cinema-teal)]/20 text-[var(--cinema-teal)]' : 'bg-[var(--cinema-elevated)] text-[var(--cinema-muted)]'
                            ]">
                                {{ user.role.toUpperCase() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center font-mono">
                            <button
                                type="button"
                                @click="updateTrustScorePrompt(user)"
                                class="hover:text-[var(--cinema-teal)] hover:underline decoration-dashed underline-offset-4"
                                :disabled="processingId === user.id"
                            >
                                {{ user.trust_score }}
                            </button>
                        </td>
                        <td class="px-4 py-3">
                            <span :class="[
                                'inline-flex items-center rounded-full px-2 py-0.5 text-xs',
                                user.banned_at ? 'bg-[var(--score-red)]/15 text-[var(--score-red)]' : 'bg-[var(--score-green)]/15 text-[var(--score-green)]'
                            ]">
                                {{ user.banned_at ? 'Banned' : 'Active' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-3">
                            <button
                                type="button"
                                @click="toggleRole(user)"
                                :disabled="processingId === user.id"
                                class="text-xs text-[var(--cinema-teal)] hover:opacity-80 disabled:opacity-50"
                            >
                                Ubah Role
                            </button>
                            <button
                                v-if="user.role !== 'admin'"
                                type="button"
                                @click="toggleBan(user)"
                                :disabled="processingId === user.id"
                                :class="[
                                    'text-xs font-medium hover:opacity-80 disabled:opacity-50',
                                    user.banned_at ? 'text-[var(--cinema-muted)]' : 'text-[var(--score-red)]'
                                ]"
                            >
                                {{ user.banned_at ? 'Unban' : 'Ban' }}
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!users.data.length">
                        <td colspan="5" class="px-4 py-12 text-center text-[var(--cinema-muted)]">Belum ada data pengguna.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav v-if="users.last_page > 1" class="flex justify-center gap-1 flex-wrap" aria-label="Pagination">
            <Link
                v-for="link in users.links"
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
