<script setup lang="ts">
/**
 * PublicLayout — layout untuk halaman publik (home, movies, movie detail).
 *
 * - Memaksa dark mode (cinematic palette) ke <html>
 * - Header navbar: logo, search, link "Movies", user menu / login
 * - Footer minimalis
 */
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted } from 'vue';

interface SharedAuth {
    user?: {
        id: number;
        name: string;
        email: string;
        role?: string;
    } | null;
}

const page = usePage<{ auth?: SharedAuth }>();
const user = computed(() => page.props.auth?.user ?? null);

// Force dark mode untuk seluruh halaman publik (override useAppearance toggle).
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
</script>

<template>
    <div class="min-h-screen bg-[var(--cinema-base)] text-[var(--cinema-text)] antialiased">
        <!-- Header -->
        <header class="sticky top-0 z-40 border-b border-[var(--cinema-border)] bg-[var(--cinema-base)]/80 backdrop-blur-md">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <!-- Logo -->
                <Link href="/" class="flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--cinema-teal)] rounded-md">
                    <div class="h-8 w-8 rounded-md bg-[var(--cinema-teal)] flex items-center justify-center font-mono font-bold text-[var(--cinema-base)]">
                        M
                    </div>
                    <span class="font-display text-2xl tracking-wide">MOVIE REVIEW</span>
                </Link>

                <!-- Center nav -->
                <nav class="hidden md:flex items-center gap-6 text-sm">
                    <Link
                        href="/movies"
                        class="text-[var(--cinema-muted)] hover:text-[var(--cinema-text)] transition-colors"
                    >
                        Films
                    </Link>
                    <Link
                        href="/"
                        class="text-[var(--cinema-muted)] hover:text-[var(--cinema-text)] transition-colors"
                    >
                        Home
                    </Link>
                </nav>

                <!-- Right side -->
                <div class="flex items-center gap-3">
                    <template v-if="user">
                        <Link
                            :href="`/u/${user.id}`"
                            class="text-sm text-[var(--cinema-muted)] hover:text-[var(--cinema-text)] transition-colors"
                        >
                            {{ user.name }}
                        </Link>
                        <Link
                            href="/my-reviews"
                            class="text-sm text-[var(--cinema-muted)] hover:text-[var(--cinema-text)] transition-colors hidden md:inline"
                        >Ulasan saya</Link>
                        <Link
                            v-if="user.role === 'admin'"
                            href="/admin"
                            class="text-sm text-[var(--cinema-teal)] hover:opacity-80 transition-opacity hidden md:inline"
                        >Admin</Link>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="rounded-md border border-[var(--cinema-border)] px-3 py-1.5 text-sm hover:bg-[var(--cinema-elevated)] transition-colors"
                        >
                            Logout
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            href="/login"
                            class="text-sm text-[var(--cinema-muted)] hover:text-[var(--cinema-text)] transition-colors"
                        >
                            Login
                        </Link>
                        <Link
                            href="/register"
                            class="rounded-md bg-[var(--cinema-teal)] px-3 py-1.5 text-sm font-medium text-[var(--cinema-base)] hover:opacity-90 transition-opacity"
                        >
                            Sign up
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Page slot -->
        <main>
            <slot />
        </main>

        <!-- Footer -->
        <footer class="mt-24 border-t border-[var(--cinema-border)] bg-[var(--cinema-base)]">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 text-sm text-[var(--cinema-muted)] flex flex-col sm:flex-row justify-between gap-4">
                <p class="font-display tracking-wider">MOVIE REVIEW · {{ new Date().getFullYear() }}</p>
                <p>Built with Laravel 13 · Inertia · Vue 3 · Cinematic Dark Theme</p>
            </div>
        </footer>
    </div>
</template>
