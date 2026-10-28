<script setup lang="ts">
defineProps<{
    label?: string;
    error?: string;
    hint?: string;
    for?: string;
}>();
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <label v-if="label" :for="$props.for" class="text-caption font-semibold text-ink-muted">{{ label }}</label>
        <slot />
        <p v-if="hint && !error" class="text-caption text-ink-subtle">{{ hint }}</p>
        <p v-if="error" class="text-caption text-danger" role="alert">{{ error }}</p>
    </div>
</template>
