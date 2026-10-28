<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        variant?: 'primary' | 'secondary' | 'ghost' | 'danger';
        size?: 'sm' | 'md';
        href?: string;
        type?: 'button' | 'submit';
        loading?: boolean;
        disabled?: boolean;
    }>(),
    { variant: 'primary', size: 'md', type: 'button', loading: false, disabled: false },
);

const classes = computed(() => [
    'inline-flex items-center justify-center gap-2 rounded-sm font-semibold transition duration-150 select-none',
    'disabled:cursor-not-allowed disabled:opacity-50 aria-disabled:cursor-not-allowed aria-disabled:opacity-50',
    props.size === 'sm' ? 'px-4 py-2 text-caption' : 'px-6 py-2.5 text-body',
    {
        primary:
            'bg-gradient-to-b from-brand to-brand-deep text-white shadow-button-primary hover:-translate-y-0.5 hover:scale-[1.02] hover:shadow-button-primary-hover',
        secondary: 'border border-border-strong bg-transparent text-ink hover:border-brand hover:text-brand',
        ghost: 'bg-transparent text-ink-muted hover:bg-surface-raised hover:text-ink',
        danger: 'bg-danger/15 text-danger border border-danger/40 hover:bg-danger/25',
    }[props.variant],
]);
</script>

<template>
    <Link v-if="href && !disabled" :href="href" :class="classes">
        <slot />
    </Link>
    <button v-else :type="type" :class="classes" :disabled="disabled || loading">
        <span v-if="loading" class="size-3.5 animate-spin rounded-pill border-2 border-current border-t-transparent" aria-hidden="true" />
        <slot />
    </button>
</template>
