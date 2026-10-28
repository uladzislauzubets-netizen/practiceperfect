<script setup lang="ts">
defineProps<{ title: string; subtitle?: string; eyebrow?: string }>();
</script>

<template>
    <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p v-if="eyebrow" class="text-eyebrow font-semibold tracking-wider text-brand uppercase">{{ eyebrow }}</p>
            <h1 class="text-hero font-bold">{{ title }}</h1>
            <p v-if="subtitle" class="mt-1 text-body text-ink-muted">{{ subtitle }}</p>
        </div>
        <div v-if="$slots.default" class="flex flex-wrap items-center gap-3"><slot /></div>
    </header>
</template>
