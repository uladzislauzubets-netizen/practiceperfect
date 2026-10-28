<script setup lang="ts">
defineOptions({ inheritAttrs: false });

const model = defineModel<string | null | undefined>();

defineProps<{ invalid?: boolean }>();
</script>

<template>
    <textarea
        v-model="model"
        v-bind="$attrs"
        :aria-invalid="invalid || undefined"
        rows="4"
        class="w-full rounded-sm border bg-page px-3 py-2 text-body text-ink placeholder:text-ink-subtle transition focus:border-brand focus:ring-2 focus:ring-brand/30 focus:outline-none"
        :class="invalid ? 'border-danger' : 'border-border-soft'"
    />
</template>
