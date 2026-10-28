<script setup lang="ts">
import FlashMessages from '@/Components/FlashMessages.vue';
import { applyBrandColor } from '@/lib/branding';
import type { SharedProps } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';

const page = usePage<SharedProps>();
const branding = computed(() => page.props.branding);
const auth = computed(() => page.props.auth);

watchEffect(() => applyBrandColor(branding.value.color));

function logout() {
    router.post('/logout');
}
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-page px-4 py-10">
        <div class="mb-6 flex items-center gap-3">
            <img v-if="branding.logo_url" :src="branding.logo_url" :alt="branding.name ?? page.props.app_name" class="size-12 rounded-sm object-contain" />
            <span v-else class="inline-flex size-12 items-center justify-center rounded-sm bg-gradient-to-b from-brand to-brand-deep text-section font-bold text-white">P</span>
            <span class="text-section font-bold">{{ branding.name ?? page.props.app_name }}</span>
        </div>

        <div class="w-full max-w-xl">
            <FlashMessages class="mb-4" />
            <slot />
        </div>

        <button v-if="auth" type="button" class="mt-6 text-caption text-ink-subtle underline hover:text-ink" @click="logout">
            Signed in as {{ auth.email }}. Log out
        </button>
    </div>
</template>
