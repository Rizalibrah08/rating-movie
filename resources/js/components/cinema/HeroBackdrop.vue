<script setup lang="ts">
/**
 * HeroBackdrop — komponen backdrop full-bleed untuk halaman detail film & home hero.
 *
 * - Backdrop image fullscreen dengan gradient bottom ke base color
 * - Saat user scroll, backdrop perlahan blur + dim (parallax effect via @vueuse/core)
 * - Ken Burns animation saat mount.
 * - Fallback: jika `image` null, render gradient gelap minimalis
 */
import { useScroll } from '@vueuse/core';
import { computed, useTemplateRef, ref } from 'vue';

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

const imageError = ref(false);

// Normalisasi scroll 0..1 berbasis tinggi viewport (1 viewport scroll → fully blurred)
const progress = computed(() => {
    if (!props.parallax) {
return 0;
}

    const vh = typeof window !== 'undefined' ? window.innerHeight : 800;

    return Math.min(1, Math.max(0, scrollY.value / vh));
});

const overlayStyle = computed(() => {
    // Baseline blur + scale jika kita pakai poster sebagai fallback (cuma 2:3, perlu di-cover penuh)
    const baseBlur = props.blurFallback ? 30 : 0;
    const baseScale = props.blurFallback ? 1.4 : 1.05; // 1.05 base scale untuk efek ken-burns agar tidak overflow

    return {
        filter: `blur(${baseBlur + (props.parallax ? progress.value * 20 : 0)}px)`,
        opacity: props.parallax ? 1 - progress.value * 0.7 : 1,
        // The scale here is combined with the CSS animation via the child element.
        transform: `scale(${baseScale + (props.parallax ? progress.value * 0.05 : 0)})`,
    };
});
</script>

<template>
    <div
        ref="sentinel"
        :class="[
            'pointer-events-none w-full overflow-hidden',
            fixed ? 'fixed inset-x-0 top-0 z-0' : 'absolute inset-x-0 top-0 z-0',
        ]"
        :style="{ height }"
    >
        <!-- Layer image with parallax styles -->
        <div class="absolute inset-0 transition-transform duration-300 ease-out origin-center" :style="overlayStyle">
            <img
                v-if="image && !imageError"
                :src="image"
                @error="imageError = true"
                alt=""
                aria-hidden="true"
                class="h-full w-full object-cover animate-ken-burns"
            />
            <div
                v-else
                class="h-full w-full bg-gradient-to-br from-[var(--cinema-surface)] to-[var(--cinema-base)]"
            />
        </div>

        <!-- Gradient overlay untuk readability + transisi mulus ke base bg -->
        <div
            class="absolute inset-0 bg-gradient-to-t from-[var(--cinema-base)] via-[var(--cinema-base)]/60 to-transparent"
        />
        <div class="absolute inset-0 bg-black/30" />
    </div>
</template>

<style scoped>
@keyframes ken-burns {
    0% { transform: scale(1); }
    100% { transform: scale(1.1) translate(-1%, -1%); }
}
.animate-ken-burns {
    animation: ken-burns 30s ease-out forwards;
    transform-origin: top left;
}
</style>
