<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import MovieForm from '@/components/admin/MovieForm.vue';

interface Genre { id: number; name: string }

interface Movie {
    id: number;
    title: string;
    slug: string;
    synopsis: string;
    year: number | null;
    duration_min: number | null;
    director: string | null;
    poster_path: string | null;
    poster_url: string | null;
    poster: string | null;
    backdrop_path: string | null;
    backdrop_url: string | null;
    backdrop: string | null;
    genre_ids: number[];
}

defineProps<{ movie: Movie; genres: Genre[] }>();
</script>

<template>
    <Head :title="`Admin · Edit ${movie.title}`" />
    <div class="space-y-6">
        <h1 class="font-display text-3xl tracking-wide">EDIT · {{ movie.title.toUpperCase() }}</h1>
        <MovieForm mode="edit" :initial="movie" :genres="genres" />
    </div>
</template>
