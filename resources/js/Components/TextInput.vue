<script setup lang="ts">
defineOptions({ inheritAttrs: false });

const model = defineModel<string | number | null | undefined>();

defineProps<{ invalid?: boolean }>();
</script>

<template>
    <input
        v-model="model"
        v-bind="$attrs"
        :aria-invalid="invalid || undefined"
        class="h-10 w-full rounded-sm border bg-page px-3 text-body text-ink placeholder:text-ink-subtle transition focus:border-brand focus:ring-2 focus:ring-brand/30 focus:outline-none disabled:opacity-50"
        :class="invalid ? 'border-danger' : 'border-border-soft'"
    />
</template>
