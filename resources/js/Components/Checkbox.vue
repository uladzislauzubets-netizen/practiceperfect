<script setup lang="ts">
defineOptions({ inheritAttrs: false });

const model = defineModel<boolean | (string | number)[]>({ default: false });

defineProps<{ label?: string; value?: string | number }>();
</script>

<template>
    <label class="inline-flex cursor-pointer items-start gap-2.5 text-body text-ink">
        <input
            v-model="model"
            v-bind="$attrs"
            :value="value"
            type="checkbox"
            class="mt-0.5 size-4 shrink-0 cursor-pointer"
        />
        <span><slot>{{ label }}</slot></span>
    </label>
</template>
