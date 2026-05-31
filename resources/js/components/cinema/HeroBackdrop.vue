<script setup lang="ts">
/**
 * HeroBackdrop — komponen backdrop full-bleed untuk halaman detail film & home hero.
 *
 * - Backdrop image fullscreen dengan gradient bottom ke base color
 * - Saat user scroll, backdrop perlahan blur + dim (parallax effect via @vueuse/core)
 * - Fallback: jika `image` null, render gradient gelap minimalis
 *
 * Implementasi parallax disempurnakan di Task 9b — di sini kita siapkan strukturnya.
 */
import { computed, useTemplateRef } from 'vue';
import { useScroll } from '@vueuse/core';

interface Props {
    image: string | null | undefined;
    /** Tinggi viewport. Default 80vh untuk hero rotator, 100vh untuk movie detail. */
    height?: string;
    /** Aktifkan efek parallax blur+dim saat scroll. */
    parallax?: boolean;
    /** Saat true, gunakan position fixed (untuk movie detail page).
     *  Saat false, position absolute relatif ke parent. */
    fixed?: boolean;
    /** Saat true, image langsung di-blur tebal & scaled — dipakai sebagai
     *  fallback ambient ketika tidak ada backdrop asli (cuma poster 2:3). */
    blurFallback?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    height: '80vh',
    parallax: false,
    fixed: false,
    blurFallback: false,
});

const sentinelRef = useTemplateRef<HTMLDivElement>('sentinel');
const { y: scrollY } = useScroll(window, { throttle: 16 });

// Normalisasi scroll 0..1 berbasis tinggi viewport (1 viewport scroll → fully blurred)
const progress = computed(() => {
    if (!props.parallax) return 0;
    const vh = typeof window !== 'undefined' ? window.innerHeight : 800;
    return Math.min(1, Math.max(0, scrollY.value / vh));
});

const overlayStyle = computed(() => {
    // Baseline blur + scale jika kita pakai poster sebagai fallback (cuma 2:3, perlu di-cover penuh)
    const baseBlur = props.blurFallback ? 30 : 0;
    const baseScale = props.blurFallback ? 1.4 : 1;

    return {
        filter: `blur(${baseBlur + (props.parallax ? progress.value * 20 : 0)}px)`,
        opacity: props.parallax ? 1 - progress.value * 0.7 : 1,
        transform: `scale(${baseScale + (props.parallax ? progress.value * 0.05 : 0)})`,
    };
});
</script>

<template>
    <div
        ref="sentinel"
        :class="[
            'pointer-events-none w-full',
            fixed ? 'fixed inset-x-0 top-0 z-0' : 'absolute inset-x-0 top-0 z-0',
        ]"
        :style="{ height }"
    >
        <!-- Layer image -->
        <div class="absolute inset-0" :style="overlayStyle">
            <img
                v-if="image"
                :src="image"
                alt=""
                aria-hidden="true"
                class="h-full w-full object-cover"
            />
            <div
                v-else
                class="h-full w-full bg-gradient-to-br from-[var(--cinema-surface)] to-[var(--cinema-base)]"
            />
        </div>

        <!-- Gradient overlay untuk readability + transisi mulus ke base bg -->
        <div
            class="absolute inset-0 bg-gradient-to-t from-[var(--cinema-base)] via-[var(--cinema-base)]/50 to-transparent"
        />
        <div class="absolute inset-0 bg-black/30" />
    </div>
</template>
