<script setup lang="ts">
import type { Option } from '@/types';

defineOptions({ inheritAttrs: false });

const model = defineModel<string | number | null | undefined>();

defineProps<{ options: Option[]; placeholder?: string; invalid?: boolean }>();
</script>

<template>
    <select
        v-model="model"
        v-bind="$attrs"
        :aria-invalid="invalid || undefined"
        class="h-10 w-full rounded-sm border bg-page px-3 text-body text-ink transition focus:border-brand focus:ring-2 focus:ring-brand/30 focus:outline-none"
        :class="invalid ? 'border-danger' : 'border-border-soft'"
    >
        <option v-if="placeholder !== undefined" value="">{{ placeholder }}</option>
        <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
    </select>
</template>
