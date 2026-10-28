<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{ label: string; value: number | string; href?: string }>();
</script>

<template>
    <component :is="href ? Link : 'div'" :href="href" class="rounded-md border border-border-soft bg-surface p-5 shadow-card-soft transition" :class="href ? 'hover:border-brand' : ''">
        <p class="text-eyebrow font-semibold tracking-wider text-ink-subtle uppercase">{{ label }}</p>
        <p class="mt-1 text-hero font-bold">{{ value }}</p>
    </component>
</template>
