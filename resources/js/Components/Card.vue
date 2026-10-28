<script setup lang="ts">
defineProps<{ title?: string; eyebrow?: string }>();
</script>

<template>
    <section class="rounded-md border border-border-soft bg-surface p-6 shadow-card-soft">
        <header v-if="title || eyebrow || $slots.actions" class="mb-4 flex items-start justify-between gap-4">
            <div>
                <p v-if="eyebrow" class="text-eyebrow font-semibold tracking-wider text-brand uppercase">{{ eyebrow }}</p>
                <h2 v-if="title" class="text-block font-bold">{{ title }}</h2>
            </div>
            <div v-if="$slots.actions" class="flex items-center gap-2"><slot name="actions" /></div>
        </header>
        <slot />
    </section>
</template>
