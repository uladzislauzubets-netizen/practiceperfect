<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{ name: string; src?: string | null; size?: number }>(), { size: 36 });

const initials = computed(() =>
    props.name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join(''),
);
</script>

<template>
    <img v-if="src" :src="src" :alt="name" :style="{ width: size + 'px', height: size + 'px' }" class="shrink-0 rounded-pill object-cover" />
    <span
        v-else
        :style="{ width: size + 'px', height: size + 'px', fontSize: size * 0.38 + 'px' }"
        class="inline-flex shrink-0 items-center justify-center rounded-pill bg-surface-raised font-semibold text-ink-muted"
        :aria-label="name"
    >
        {{ initials }}
    </span>
</template>
