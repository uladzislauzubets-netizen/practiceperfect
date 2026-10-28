<script setup lang="ts">
import type { SharedProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage<SharedProps>();
const flash = computed(() => page.props.flash);
</script>

<template>
    <div class="flex flex-col gap-3 empty:hidden" aria-live="polite">
        <div v-if="flash?.success" class="rounded-sm border border-brand/40 bg-brand/10 px-4 py-3 text-body text-brand-soft" role="status">
            {{ flash.success }}
        </div>
        <div v-if="flash?.error" class="rounded-sm border border-danger/40 bg-danger/10 px-4 py-3 text-body text-danger" role="alert">
            {{ flash.error }}
        </div>
    </div>
</template>
