<script setup lang="ts">
/**
 * ScoreBadge — komponen rating Metacritic-style
 *
 * Warna otomatis berdasarkan skor 0..100:
 *   - 75..100 → green   (positive)
 *   - 50..74  → yellow  (mixed)
 *   - 0..49   → red     (negative)
 *   - null    → grey    "tbd"
 *
 * Ukuran:  sm (28px) | md (44px) | lg (72px)
 */
import { computed } from 'vue';

interface Props {
    score: number | null | undefined;
    size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
});

type Category = 'green' | 'yellow' | 'red' | 'grey';

const category = computed<Category>(() => {
    if (props.score === null || props.score === undefined) {
return 'grey';
}

    if (props.score >= 75) {
return 'green';
}

    if (props.score >= 50) {
return 'yellow';
}

    return 'red';
});

const display = computed<string>(() =>
    props.score === null || props.score === undefined
        ? 'tbd'
        : Math.round(props.score).toString(),
);

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'h-7 w-7 text-[14px]';
        case 'lg':
            return 'h-[72px] w-[72px] text-[36px]';
        case 'md':
        default:
            return 'h-11 w-11 text-[22px]';
    }
});

const colorClasses = computed(() => {
    switch (category.value) {
        case 'green':
            return 'bg-[var(--score-green)] text-white';
        case 'yellow':
            return 'bg-[var(--score-yellow)] text-[var(--cinema-base)]';
        case 'red':
            return 'bg-[var(--score-red)] text-white';
        case 'grey':
            return 'bg-[var(--score-grey)] text-white';
    }

    return '';
});

const ariaLabel = computed(() =>
    props.score === null || props.score === undefined
        ? 'Skor belum tersedia'
        : `Skor ${Math.round(props.score)} dari 100`,
);
</script>

<template>
    <div
        :class="[
            'inline-flex items-center justify-center rounded-md font-mono font-bold leading-none shadow-sm transition-colors duration-150',
            sizeClasses,
            colorClasses,
        ]"
        :aria-label="ariaLabel"
        :data-category="category"
    >
        {{ display }}
    </div>
</template>
