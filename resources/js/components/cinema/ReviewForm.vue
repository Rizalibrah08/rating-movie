<script setup lang="ts">
/**
 * ReviewForm — submit ulasan baru.
 *
 * Fitur:
 * - Slider 0..100 (step 1) + preset cepat 25/50/75/90
 * - Live ScoreBadge preview yang berubah warna saat slider digeser
 * - Live filter feedback: char counter, word counter, hint URL/keyword
 *   (server-side filter pipeline tetap menjadi sumber kebenaran final)
 * - Tampilkan error 422 dari pipeline di field body
 */
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ScoreBadge from '@/components/cinema/ScoreBadge.vue';

interface Props {
    movieId: number;
    movieSlug: string;
    blockedKeywords: Array<{ keyword: string; category: string }>;
}

const props = defineProps<Props>();

const form = useForm({
    movie_id: props.movieId,
    rating: 70,
    body: '',
    website: '', // honeypot — tidak diisi user normal, dicoret oleh CSS display:none
});

// Preset buttons
const presets = [25, 50, 75, 90];

function setRating(v: number) {
    form.rating = v;
}

// === Client-side filter hints (informational only) ===
const charCount = computed(() => form.body.trim().length);
const wordCount = computed(() => {
    const trimmed = form.body.trim();

    if (!trimmed) {
return 0;
}

    return trimmed
        .toLowerCase()
        .split(/\s+/)
        .filter((w) => w.length >= 2)
        .length;
});

const URL_PATTERN = /\b(?:https?:\/\/|www\.)\S+|\b[a-z0-9-]+\.(?:com|net|org|id|co|io|me|tv|app|xyz|info|biz|live|site|online|store|shop)\b/i;

/** Homoglyph map sinkron dengan PHP TextNormalizer::HOMOGLYPH_MAP */
const HOMOGLYPH_MAP: Record<string, string> = {
    // Cyrillic
    а: 'a', А: 'a', е: 'e', Е: 'e', о: 'o', О: 'o', р: 'p', Р: 'p',
    с: 'c', С: 'c', х: 'x', Х: 'x', і: 'i', І: 'i', ј: 'j', Ј: 'j',
    ѕ: 's', Ѕ: 's', у: 'y', У: 'y', к: 'k', К: 'k', м: 'm', М: 'm',
    т: 't', Т: 't', н: 'h', Н: 'h',
    // Greek
    α: 'a', Α: 'a', ε: 'e', Ε: 'e', ο: 'o', Ο: 'o', ρ: 'p', Ρ: 'p',
    ν: 'v', Ν: 'n', κ: 'k', Κ: 'k', τ: 't', Τ: 't', υ: 'u', Υ: 'y',
    ι: 'i', Ι: 'i',
};

/** Extended leet map sinkron dengan PHP TextNormalizer::LEETSPEAK_MAP */
const LEET_MAP: Record<string, string> = {
    '0': 'o', '1': 'i', '3': 'e', '4': 'a', '5': 's',
    '7': 't', '@': 'a', '$': 's', '8': 'b', '!': 'i',
    '€': 'e', '6': 'g', '#': 'h', '9': 'q', '+': 't',
    '%': 'x', '2': 'z', '(': 'c', '|': 'i',
};

/**
 * Normalisasi teks — sinkron dengan PHP TextNormalizer::normalize().
 * Menangkap: zero-width, homoglyph, diacritics, separator, spaced-chars, leet, repeat.
 */
function normalizeForCheck(text: string): string {
    // 1. Lowercase
    let t = text.toLowerCase();

    // 2. Strip zero-width & invisible chars
    t = t.replace(/[\u00AD\u034F\u061C\u115F\u1160\u17B4\u17B5\u180B-\u180D\u200B-\u200F\u202A-\u202E\u2060-\u2064\u2066-\u206F\u3164\uFE00-\uFE0F\uFEFF\uFFA0]/g, '');

    // 3. Homoglyph map
    t = t.replace(/[аАеЕоОрРсСхХіІјЈѕЅуУкКмМтТнН\u03B1\u0391\u03B5\u0395\u03BF\u039F\u03C1\u03A1\u03BD\u039D\u03BA\u039A\u03C4\u03A4\u03C5\u03A5\u03B9\u0399]/g, (c) => HOMOGLYPH_MAP[c] ?? c);

    // 4. Strip diacritics / accents (NFD → remove combining marks → NFC)
    t = t.normalize('NFD').replace(/[\u0300-\u036F]/g, '').normalize('NFC');

    // 5. Strip char separators: "j.e.l.e.k" → "jelek"
    t = t.replace(/([a-z])[.\-_*\\/]{1,3}(?=[a-z])/g, '$1');

    // 6. Collapse spaced-out chars: "j e l e k" → "jelek"
    t = t.replace(/(?<!\w)((?:[a-z] ){2,}[a-z])(?!\w)/g, (m) => m.replace(/ /g, ''));

    // 7. Extended leet map
    t = t.replace(/[013456789@$!€#%+|(]/g, (c) => LEET_MAP[c] ?? c);

    // 8. Compress repeated chars ≥2: "jeleek" → "jelek"
    t = t.replace(/(.)\1+/g, '$1');

    // 9. Collapse whitespace
    return t.replace(/\s+/g, ' ').trim();
}

const hintIssues = computed(() => {
    const issues: Array<{ kind: 'warn' | 'info'; message: string }> = [];
    const body = form.body;

    if (charCount.value > 0 && charCount.value < 30) {
        issues.push({ kind: 'warn', message: `Min 30 karakter (${charCount.value}/30)` });
    }

    if (wordCount.value > 0 && wordCount.value < 5) {
        issues.push({ kind: 'warn', message: `Min 5 kata bermakna (${wordCount.value}/5)` });
    }

    if (URL_PATTERN.test(body)) {
        issues.push({ kind: 'warn', message: 'Tautan/URL tidak diperbolehkan' });
    }

    // Cek keyword (informational, server tetap final)
    const normalized = normalizeForCheck(body);

    for (const entry of props.blockedKeywords) {
        const escaped = entry.keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&').replace(/\s+/g, '\\s+');
        const re = new RegExp('\\b' + escaped + '\\b', 'iu');

        if (re.test(normalized)) {
            issues.push({ kind: 'warn', message: `Mengandung kata terlarang: "${entry.keyword}"` });
            break; // satu cukup
        }
    }

    return issues;
});

const isClean = computed(() => hintIssues.value.length === 0 && charCount.value >= 30);

function submit() {
    form.post('/reviews', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('body');
            form.rating = 70;
        },
    });
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-5">
        <!-- Honeypot field — disembunyikan dari user, hanya bot yang isi -->
        <div aria-hidden="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">
            <label>Website (jangan diisi)
                <input type="text" v-model="form.website" tabindex="-1" autocomplete="off" />
            </label>
        </div>
        <!-- Slider + ScoreBadge live -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <label class="font-display text-lg tracking-wide">BERI SKOR</label>
                <ScoreBadge :score="form.rating" size="md" />
            </div>

            <input
                type="range"
                min="0"
                max="100"
                step="1"
                v-model.number="form.rating"
                class="w-full accent-[var(--cinema-teal)] cursor-pointer"
                aria-label="Skor 0 sampai 100"
            />
            <div class="flex justify-between text-[10px] text-[var(--cinema-muted)] font-mono mt-1 px-0.5">
                <span>0</span><span>25</span><span>50</span><span>75</span><span>100</span>
            </div>

            <div class="flex gap-2 mt-3">
                <button
                    v-for="p in presets"
                    :key="p"
                    type="button"
                    @click="setRating(p)"
                    :class="[
                        'rounded-md border px-3 py-1 text-xs font-mono transition-colors',
                        form.rating === p
                            ? 'bg-[var(--cinema-teal)] text-[var(--cinema-base)] border-transparent'
                            : 'border-[var(--cinema-border)] text-[var(--cinema-muted)] hover:text-[var(--cinema-text)] hover:border-[var(--cinema-teal)]',
                    ]"
                >{{ p }}</button>
            </div>
        </div>

        <!-- Body textarea -->
        <div>
            <label class="font-display text-lg tracking-wide block mb-2">ULASANMU</label>
            <textarea
                v-model="form.body"
                rows="5"
                placeholder="Bagaimana pendapatmu tentang film ini? (min 30 karakter, 5 kata bermakna)"
                class="w-full rounded-md bg-[var(--cinema-base)]/50 border border-[var(--cinema-border)] px-4 py-3 text-sm text-[var(--cinema-text)] placeholder:text-[var(--cinema-muted)] focus:outline-none focus:ring-2 focus:ring-[var(--cinema-teal)] resize-y"
                :class="{ 'border-[var(--score-red)]/60': form.errors.body }"
                maxlength="5000"
            ></textarea>

            <div class="mt-2 flex items-start justify-between gap-3 flex-wrap">
                <!-- Live filter feedback -->
                <div class="text-xs space-y-0.5">
                    <p
                        v-if="charCount === 0"
                        class="text-[var(--cinema-muted)]"
                    >Mulai mengetik untuk melihat indikator…</p>
                    <p
                        v-else-if="isClean"
                        class="text-[var(--score-green)]"
                    >✓ Ulasan terlihat baik · siap dikirim</p>
                    <p
                        v-for="(issue, idx) in hintIssues"
                        :key="idx"
                        class="text-[var(--score-yellow)]"
                    >⚠ {{ issue.message }}</p>
                </div>
                <!-- Char counter -->
                <div class="text-xs font-mono text-[var(--cinema-muted)]">
                    {{ charCount }} / 5000 karakter · {{ wordCount }} kata
                </div>
            </div>

            <p v-if="form.errors.body" class="text-xs text-[var(--score-red)] mt-2">
                {{ form.errors.body }}
            </p>
            <p v-if="form.errors.rating" class="text-xs text-[var(--score-red)] mt-1">
                {{ form.errors.rating }}
            </p>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-end gap-3 pt-2 border-t border-[var(--cinema-border)]">
            <button
                type="submit"
                :disabled="form.processing || charCount < 1"
                class="rounded-md bg-[var(--cinema-teal)] px-5 py-2.5 text-sm font-semibold text-[var(--cinema-base)] hover:opacity-90 disabled:opacity-50 transition-opacity"
            >
                {{ form.processing ? 'Mengirim…' : 'Kirim Ulasan' }}
            </button>
        </div>
    </form>
</template>
