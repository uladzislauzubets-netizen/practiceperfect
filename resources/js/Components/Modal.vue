<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = withDefaults(defineProps<{ show: boolean; title: string; size?: 'sm' | 'md' | 'lg' }>(), { size: 'md' });
const emit = defineEmits<{ close: [] }>();

const panel = ref<HTMLElement | null>(null);

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') {
        emit('close');
    }
}

watch(
    () => props.show,
    async (open) => {
        if (open) {
            document.addEventListener('keydown', onKeydown);
            await nextTick();
            panel.value?.querySelector<HTMLElement>('input, textarea, select, button')?.focus();
        } else {
            document.removeEventListener('keydown', onKeydown);
        }
    },
    { immediate: true },
);

onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" :aria-label="title">
            <div class="absolute inset-0 bg-black/70" @click="emit('close')" />
            <div
                ref="panel"
                class="relative max-h-[90vh] w-full overflow-y-auto rounded-md border border-border-soft bg-surface p-6 shadow-card-strong"
                :class="{ sm: 'max-w-md', md: 'max-w-xl', lg: 'max-w-3xl' }[size]"
            >
                <h2 class="mb-4 text-block font-bold">{{ title }}</h2>
                <slot />
                <div v-if="$slots.footer" class="mt-6 flex flex-wrap justify-end gap-3"><slot name="footer" /></div>
            </div>
        </div>
    </Teleport>
</template>
