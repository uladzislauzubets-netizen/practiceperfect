<script setup lang="ts">
defineProps<{ title: string; message?: string }>();
</script>

<template>
    <div class="rounded-md border border-dashed border-border-strong px-6 py-10 text-center">
        <p class="text-card font-semibold">{{ title }}</p>
        <p v-if="message" class="mx-auto mt-1 max-w-md text-body text-ink-muted">{{ message }}</p>
        <div v-if="$slots.default" class="mt-4 flex justify-center"><slot /></div>
    </div>
</template>
