<script setup lang="ts">
/**
 * MovieForm — komponen form untuk create dan edit film.
 *
 * Mendukung dual mode poster (file/URL) dan dual mode backdrop (file/URL/null).
 * Saat edit, terima prop `initial` dengan data existing dari controller.
 */
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Genre {
    id: number;
    name: string;
}

interface InitialMovie {
    id?: number;
    title: string;
    synopsis: string;
    year: number | null;
    duration_min: number | null;
    director: string | null;
    poster: string | null;
    poster_path?: string | null;
    poster_url: string | null;
    backdrop: string | null;
    backdrop_path?: string | null;
    backdrop_url: string | null;
    genre_ids?: number[];
}

const props = defineProps<{
    mode: 'create' | 'edit';
    initial?: InitialMovie;
    genres: Genre[];
}>();

type PosterMode = 'file' | 'url';

const posterMode = ref<PosterMode>(props.initial?.poster_url ? 'url' : 'file');
const backdropMode = ref<PosterMode>(props.initial?.backdrop_url ? 'url' : 'file');

const form = useForm({
    title: props.initial?.title ?? '',
    synopsis: props.initial?.synopsis ?? '',
    year: props.initial?.year ?? new Date().getFullYear(),
    duration_min: props.initial?.duration_min ?? null,
    director: props.initial?.director ?? '',
    poster_file: null as File | null,
    poster_url: props.initial?.poster_url ?? '',
    backdrop_file: null as File | null,
    backdrop_url: props.initial?.backdrop_url ?? '',
    remove_poster: false,
    remove_backdrop: false,
    genres: (props.initial?.genre_ids ?? []) as number[],
    _method: props.mode === 'edit' ? 'put' : 'post',
});

const posterPreview = ref<string | null>(props.initial?.poster ?? null);
const backdropPreview = ref<string | null>(props.initial?.backdrop ?? null);

const posterInputRef = ref<HTMLInputElement | null>(null);
const backdropInputRef = ref<HTMLInputElement | null>(null);

function onPosterFile(e: Event) {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    form.poster_file = file;

    if (file) {
        posterPreview.value = URL.createObjectURL(file);
        form.remove_poster = false;
    }
}

function onBackdropFile(e: Event) {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    form.backdrop_file = file;

    if (file) {
        backdropPreview.value = URL.createObjectURL(file);
        form.remove_backdrop = false;
    }
}

function clearPoster() {
    form.poster_file = null;
    form.poster_url = '';
    form.remove_poster = true;
    posterPreview.value = null;
}

function clearBackdrop() {
    form.backdrop_file = null;
    form.backdrop_url = '';
    form.remove_backdrop = true;
    backdropPreview.value = null;
}

function toggleGenre(id: number) {
    const idx = form.genres.indexOf(id);

    if (idx >= 0) {
form.genres.splice(idx, 1);
} else {
form.genres.push(id);
}
}

const action = computed(() =>
    props.mode === 'create' ? '/admin/movies' : `/admin/movies/${props.initial?.id}`,
);

function submit() {
    // Inertia useForm + spoofed PUT for edit (uses POST + _method=put because of multipart)
    if (props.mode === 'create') {
        form.post(action.value, { forceFormData: true });
    } else {
        form.post(action.value, { forceFormData: true });
    }
}

function cancel() {
    router.get('/admin/movies');
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-8 max-w-4xl">
        <!-- Title + sinopsis + meta -->
        <section class="space-y-4">
            <div>
                <label class="block text-sm mb-1">Judul</label>
                <input
                    v-model="form.title"
                    type="text"
                    class="w-full rounded-md bg-[var(--cinema-surface)] border border-[var(--cinema-border)] px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--cinema-teal)]"
                />
                <p v-if="form.errors.title" class="text-xs text-[var(--score-red)] mt-1">{{ form.errors.title }}</p>
            </div>

            <div>
                <label class="block text-sm mb-1">Sinopsis</label>
                <textarea
                    v-model="form.synopsis"
                    rows="4"
                    class="w-full rounded-md bg-[var(--cinema-surface)] border border-[var(--cinema-border)] px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--cinema-teal)]"
                ></textarea>
                <p v-if="form.errors.synopsis" class="text-xs text-[var(--score-red)] mt-1">{{ form.errors.synopsis }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm mb-1">Tahun</label>
                    <input
                        v-model.number="form.year"
                        type="number"
                        min="1900"
                        max="2100"
                        class="w-full rounded-md bg-[var(--cinema-surface)] border border-[var(--cinema-border)] px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--cinema-teal)]"
                    />
                    <p v-if="form.errors.year" class="text-xs text-[var(--score-red)] mt-1">{{ form.errors.year }}</p>
                </div>
                <div>
                    <label class="block text-sm mb-1">Durasi (menit)</label>
                    <input
                        v-model.number="form.duration_min"
                        type="number"
                        min="30"
                        max="500"
                        placeholder="opsional"
                        class="w-full rounded-md bg-[var(--cinema-surface)] border border-[var(--cinema-border)] px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--cinema-teal)]"
                    />
                </div>
                <div>
                    <label class="block text-sm mb-1">Sutradara</label>
                    <input
                        v-model="form.director"
                        type="text"
                        placeholder="opsional"
                        class="w-full rounded-md bg-[var(--cinema-surface)] border border-[var(--cinema-border)] px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--cinema-teal)]"
                    />
                </div>
            </div>
        </section>

        <!-- Poster -->
        <section class="space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="font-display text-xl tracking-wide">POSTER <span class="text-xs text-[var(--score-red)]">*wajib</span></h3>
                <div class="inline-flex rounded-md bg-[var(--cinema-surface)] p-1 text-xs">
                    <button
                        type="button"
                        @click="posterMode = 'file'"
                        :class="['px-3 py-1 rounded', posterMode === 'file' ? 'bg-[var(--cinema-elevated)] text-[var(--cinema-text)]' : 'text-[var(--cinema-muted)]']"
                    >Upload</button>
                    <button
                        type="button"
                        @click="posterMode = 'url'"
                        :class="['px-3 py-1 rounded', posterMode === 'url' ? 'bg-[var(--cinema-elevated)] text-[var(--cinema-text)]' : 'text-[var(--cinema-muted)]']"
                    >URL</button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-[200px_1fr] gap-4">
                <div class="aspect-[2/3] rounded-md overflow-hidden bg-[var(--cinema-elevated)] border border-[var(--cinema-border)]">
                    <img v-if="posterPreview" :src="posterPreview" alt="Poster preview" class="h-full w-full object-cover" />
                    <div v-else class="h-full w-full flex items-center justify-center text-xs text-[var(--cinema-muted)]">No poster</div>
                </div>
                <div class="space-y-2">
                    <template v-if="posterMode === 'file'">
                        <label
                            class="relative flex w-full cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-[var(--cinema-border)] bg-[var(--cinema-surface)] py-6 px-4 text-center hover:bg-[var(--cinema-elevated)] transition-colors"
                            @dragover.prevent
                            @drop.prevent="(e) => { const file = e.dataTransfer?.files?.[0]; if(file) { onPosterFile({ target: { files: [file] } } as any); } }"
                        >
                            <svg class="mb-2 h-8 w-8 text-[var(--cinema-muted)]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" /></svg>
                            <span class="text-sm font-medium text-[var(--cinema-text)]">Pilih atau letakkan poster di sini</span>
                            <span class="text-xs text-[var(--cinema-muted)] mt-1">JPG, PNG, WebP (Maks 4 MB)</span>
                            <input
                                ref="posterInputRef"
                                type="file"
                                accept="image/*"
                                @change="onPosterFile"
                                class="hidden"
                            />
                        </label>
                    </template>
                    <template v-else>
                        <input
                            v-model="form.poster_url"
                            type="url"
                            placeholder="https://example.com/poster.jpg"
                            class="w-full rounded-md bg-[var(--cinema-surface)] border border-[var(--cinema-border)] px-4 py-2 text-sm"
                            @input="posterPreview = (form.poster_url || null)"
                        />
                    </template>
                    <button
                        v-if="posterPreview"
                        type="button"
                        @click="clearPoster"
                        class="text-xs text-[var(--score-red)] hover:opacity-80"
                    >Hapus poster</button>
                    <p v-if="form.errors.poster_file" class="text-xs text-[var(--score-red)]">{{ form.errors.poster_file }}</p>
                    <p v-if="form.errors.poster_url" class="text-xs text-[var(--score-red)]">{{ form.errors.poster_url }}</p>
                </div>
            </div>
        </section>

        <!-- Backdrop -->
        <section class="space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="font-display text-xl tracking-wide">BACKDROP <span class="text-xs text-[var(--cinema-muted)]">opsional · digunakan untuk hero banner</span></h3>
                <div class="inline-flex rounded-md bg-[var(--cinema-surface)] p-1 text-xs">
                    <button
                        type="button"
                        @click="backdropMode = 'file'"
                        :class="['px-3 py-1 rounded', backdropMode === 'file' ? 'bg-[var(--cinema-elevated)] text-[var(--cinema-text)]' : 'text-[var(--cinema-muted)]']"
                    >Upload</button>
                    <button
                        type="button"
                        @click="backdropMode = 'url'"
                        :class="['px-3 py-1 rounded', backdropMode === 'url' ? 'bg-[var(--cinema-elevated)] text-[var(--cinema-text)]' : 'text-[var(--cinema-muted)]']"
                    >URL</button>
                </div>
            </div>

            <div class="space-y-2">
                <div class="aspect-video rounded-md overflow-hidden bg-[var(--cinema-elevated)] border border-[var(--cinema-border)]">
                    <img v-if="backdropPreview" :src="backdropPreview" alt="Backdrop preview" class="h-full w-full object-cover" />
                    <div v-else class="h-full w-full flex items-center justify-center text-xs text-[var(--cinema-muted)]">No backdrop (akan fallback ke poster blur)</div>
                </div>
                <template v-if="backdropMode === 'file'">
                    <label
                        class="relative flex w-full cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-[var(--cinema-border)] bg-[var(--cinema-surface)] py-8 px-4 text-center hover:bg-[var(--cinema-elevated)] transition-colors"
                        @dragover.prevent
                        @drop.prevent="(e) => { const file = e.dataTransfer?.files?.[0]; if(file) { onBackdropFile({ target: { files: [file] } } as any); } }"
                    >
                        <svg class="mb-2 h-10 w-10 text-[var(--cinema-muted)]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" /></svg>
                        <span class="text-sm font-medium text-[var(--cinema-text)]">Pilih atau letakkan backdrop di sini</span>
                        <span class="text-xs text-[var(--cinema-muted)] mt-1">JPG, PNG, WebP (Maks 8 MB). Rasio ideal 16:9.</span>
                        <input
                            ref="backdropInputRef"
                            type="file"
                            accept="image/*"
                            @change="onBackdropFile"
                            class="hidden"
                        />
                    </label>
                </template>
                <template v-else>
                    <input
                        v-model="form.backdrop_url"
                        type="url"
                        placeholder="https://example.com/backdrop.jpg"
                        class="w-full rounded-md bg-[var(--cinema-surface)] border border-[var(--cinema-border)] px-4 py-2 text-sm"
                        @input="backdropPreview = (form.backdrop_url || null)"
                    />
                </template>
                <button
                    v-if="backdropPreview"
                    type="button"
                    @click="clearBackdrop"
                    class="text-xs text-[var(--score-red)] hover:opacity-80"
                >Hapus backdrop</button>
                <p v-if="form.errors.backdrop_file" class="text-xs text-[var(--score-red)]">{{ form.errors.backdrop_file }}</p>
                <p v-if="form.errors.backdrop_url" class="text-xs text-[var(--score-red)]">{{ form.errors.backdrop_url }}</p>
            </div>
        </section>

        <!-- Genres -->
        <section>
            <h3 class="font-display text-xl tracking-wide mb-2">GENRES</h3>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="g in genres"
                    :key="g.id"
                    type="button"
                    @click="toggleGenre(g.id)"
                    :class="[
                        'rounded-full border px-3 py-1 text-xs transition-colors',
                        form.genres.includes(g.id)
                            ? 'bg-[var(--cinema-teal)] text-[var(--cinema-base)] border-transparent'
                            : 'border-[var(--cinema-border)] text-[var(--cinema-muted)] hover:text-[var(--cinema-text)] hover:border-[var(--cinema-teal)]',
                    ]"
                >{{ g.name }}</button>
            </div>
        </section>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-[var(--cinema-border)]">
            <button
                type="button"
                @click="cancel"
                class="rounded-md border border-[var(--cinema-border)] px-4 py-2 text-sm hover:bg-[var(--cinema-elevated)]"
            >Batal</button>
            <button
                type="submit"
                :disabled="form.processing"
                class="rounded-md bg-[var(--cinema-teal)] px-5 py-2 text-sm font-medium text-[var(--cinema-base)] hover:opacity-90 disabled:opacity-50"
            >{{ mode === 'create' ? 'Simpan Film' : 'Update Film' }}</button>
        </div>
    </form>
</template>
