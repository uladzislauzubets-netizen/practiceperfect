<script setup lang="ts">
withDefaults(defineProps<{ tone?: 'neutral' | 'success' | 'warning' | 'danger' | 'info' }>(), { tone: 'neutral' });
</script>

<template>
    <span
        class="inline-flex items-center rounded-pill px-2.5 py-0.5 text-caption font-semibold"
        :class="{
            neutral: 'bg-surface-raised text-ink-muted',
            success: 'bg-brand/15 text-brand-soft',
            warning: 'bg-warning/15 text-warning',
            danger: 'bg-danger/15 text-danger',
            info: 'bg-info/15 text-info',
        }[tone]"
    >
        <slot />
    </span>
</template>
