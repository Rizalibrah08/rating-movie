<script setup lang="ts">
/**
 * GlassPanel — container glassmorphism untuk content yang naik di atas hero backdrop.
 * Pakai untuk wrapper review section di Movie Detail (Task 9b).
 */
interface Props {
    /** 'subtle' = light glass (untuk ReviewCard di dalam panel),
     *  'strong' = dark glass dengan border atas (untuk container besar) */
    variant?: 'subtle' | 'strong';
    as?: string;
}

withDefaults(defineProps<Props>(), {
    variant: 'subtle',
    as: 'div',
});
</script>

<template>
    <component
        :is="as"
        :class="[
            'rounded-xl',
            variant === 'strong' ? 'glass-strong rounded-t-3xl border-t' : 'glass',
        ]"
    >
        <slot />
    </component>
</template>
