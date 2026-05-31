<script setup lang="ts">
/**
 * AdminLayout — sidebar layout untuk halaman admin.
 *
 * - Force dark mode (cinematic)
 * - Sidebar kiri: nav menu Movies, Genres (Moderation/Reports/Dashboard nanti di Task 10/11/12)
 * - Header atas: judul halaman + user menu
 * - Flash toast support via flash.success/flash.error
 */
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface NavItem {
    label: string;
    href: string;
    match: string;
    /** Saat true, hanya match exact path. Untuk Dashboard '/admin'
     *  agar tidak ikut highlight saat berada di sub-route. */
    exact?: boolean;
}

const items: NavItem[] = [
    { label: 'Dashboard', href: '/admin', match: '/admin', exact: true },
    { label: 'Movies', href: '/admin/movies', match: '/admin/movies' },
    { label: 'Genres', href: '/admin/genres', match: '/admin/genres' },
    { label: 'Keywords', href: '/admin/keywords', match: '/admin/keywords' },
    { label: 'Moderation', href: '/admin/moderation', match: '/admin/moderation' },
    { label: 'Reports', href: '/admin/reports', match: '/admin/reports' },
];

const page = usePage<any>();
const user = computed(() => page.props.auth?.user ?? null);
const flashSuccess = ref<string | null>(null);
const flashError = ref<string | null>(null);

function pickFlash() {
    flashSuccess.value = page.props.flash?.success ?? null;
    flashError.value = page.props.flash?.error ?? null;
    if (flashSuccess.value || flashError.value) {
        setTimeout(() => {
            flashSuccess.value = null;
            flashError.value = null;
        }, 4000);
    }
}

watch(() => page.props.flash, () => pickFlash(), { immediate: true, deep: true });

const currentPath = computed(() => page.url.split('?')[0]);

const breadcrumbText = computed(() => {
    const parts = currentPath.value.split('/').filter(Boolean);
    if (parts.length === 0) return 'Admin';
    return parts.map((p) => p.charAt(0).toUpperCase() + p.slice(1)).join(' / ');
});

function isActive(item: NavItem): boolean {
    if (item.exact) {
        return currentPath.value === item.match;
    }
    return currentPath.value === item.match || currentPath.value.startsWith(item.match + '/');
}

let prevDarkClass = false;
onMounted(() => {
    if (typeof document !== 'undefined') {
        prevDarkClass = document.documentElement.classList.contains('dark');
        document.documentElement.classList.add('dark');
    }
});
onBeforeUnmount(() => {
    if (typeof document !== 'undefined' && !prevDarkClass) {
        document.documentElement.classList.remove('dark');
    }
});

function logout() {
    router.post('/logout');
}
</script>

<template>
    <div class="min-h-screen bg-[var(--cinema-base)] text-[var(--cinema-text)] antialiased flex">
        <!-- Sidebar -->
        <aside class="hidden md:flex w-60 shrink-0 flex-col border-r border-[var(--cinema-border)] bg-[var(--cinema-surface)]">
            <Link href="/admin" class="flex items-center gap-2 px-5 h-16 border-b border-[var(--cinema-border)]">
                <div class="h-8 w-8 rounded-md bg-[var(--cinema-teal)] flex items-center justify-center font-mono font-bold text-[var(--cinema-base)]">
                    M
                </div>
                <span class="font-display text-xl tracking-wide">ADMIN</span>
            </Link>
            <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
                <Link
                    v-for="item in items"
                    :key="item.href"
                    :href="item.href"
                    :class="[
                        'flex items-center px-3 py-2 rounded-md transition-colors',
                        isActive(item)
                            ? 'bg-[var(--cinema-elevated)] text-[var(--cinema-teal)]'
                            : 'text-[var(--cinema-muted)] hover:text-[var(--cinema-text)] hover:bg-[var(--cinema-elevated)]',
                    ]"
                >
                    {{ item.label }}
                </Link>
            </nav>
            <div class="px-5 py-3 border-t border-[var(--cinema-border)] text-xs text-[var(--cinema-muted)]">
                <Link href="/" class="hover:text-[var(--cinema-text)] transition-colors">← Lihat situs publik</Link>
            </div>
        </aside>

        <!-- Main area -->
        <div class="flex-1 flex flex-col min-w-0">
            <header class="h-16 px-6 border-b border-[var(--cinema-border)] flex items-center justify-between bg-[var(--cinema-base)]/80 backdrop-blur-md">
                <div class="text-sm text-[var(--cinema-muted)]">
                    {{ breadcrumbText }}
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <span v-if="user" class="text-[var(--cinema-muted)]">{{ user.name }}</span>
                    <button
                        type="button"
                        @click="logout"
                        class="rounded-md border border-[var(--cinema-border)] px-3 py-1.5 hover:bg-[var(--cinema-elevated)]"
                    >
                        Logout
                    </button>
                </div>
            </header>

            <!-- Flash banners -->
            <div v-if="flashSuccess" class="mx-6 mt-4 px-4 py-3 rounded-md border border-[var(--score-green)]/30 bg-[var(--score-green)]/10 text-[var(--score-green)] text-sm">
                {{ flashSuccess }}
            </div>
            <div v-if="flashError" class="mx-6 mt-4 px-4 py-3 rounded-md border border-[var(--score-red)]/30 bg-[var(--score-red)]/10 text-[var(--score-red)] text-sm">
                {{ flashError }}
            </div>

            <main class="flex-1 p-6 overflow-x-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
