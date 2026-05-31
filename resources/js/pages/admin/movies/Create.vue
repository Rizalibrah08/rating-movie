<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import MovieForm from '@/components/admin/MovieForm.vue';

interface Genre { id: number; name: string }

defineProps<{ genres: Genre[] }>();
</script>

<template>
    <Head title="Admin · Tambah Film" />
    <div class="space-y-6">
        <h1 class="font-display text-3xl tracking-wide">TAMBAH FILM</h1>
        <MovieForm mode="create" :genres="genres" />
    </div>
</template>
