<script setup lang="ts">
import type { Impersonation } from '@/types';
import { router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{ impersonation: Impersonation }>();

const now = ref(Date.now());
let timer: ReturnType<typeof setInterval> | undefined;

onMounted(() => (timer = setInterval(() => (now.value = Date.now()), 1000)));
onBeforeUnmount(() => clearInterval(timer));

const remaining = computed(() => {
    const seconds = Math.max(0, Math.floor((new Date(props.impersonation.expires_at).getTime() - now.value) / 1000));

    return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
});

function exit() {
    router.post('/impersonation/stop');
}
</script>

<template>
    <div class="sticky top-0 z-40 flex flex-wrap items-center justify-center gap-x-4 gap-y-1 bg-warning px-4 py-2 text-body font-semibold text-black" role="status">
        <span>Viewing as {{ impersonation.target_name }} ({{ impersonation.target_role }})</span>
        <span class="font-normal">Session ends in {{ remaining }}</span>
        <button type="button" class="rounded-xs bg-black px-3 py-1 text-caption font-semibold text-warning hover:bg-black/80" @click="exit">
            Exit Impersonation
        </button>
    </div>
</template>
