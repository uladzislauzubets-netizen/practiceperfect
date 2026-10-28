<script setup lang="ts">
const model = defineModel<boolean>({ default: false });

defineProps<{ label: string }>();
</script>

<template>
    <button
        type="button"
        role="switch"
        :aria-checked="model"
        :aria-label="label"
        class="relative h-6 w-[39px] shrink-0 rounded-pill border transition"
        :class="model ? 'border-brand bg-brand' : 'border-border-strong bg-page'"
        @click="model = !model"
    >
        <span class="absolute top-0.5 left-0.5 size-[18px] rounded-pill bg-white transition-transform" :class="model ? 'translate-x-[15px]' : ''" />
    </button>
</template>
