<script setup lang="ts">
import type { ContextData } from '@/types';
import { router } from '@inertiajs/vue3';

const props = defineProps<{ context: ContextData }>();

function change(event: Event) {
    const key = (event.target as HTMLSelectElement).value;
    const option = props.context.groups.flatMap((group) => group.options).find((candidate) => candidate.key === key);

    if (option) {
        router.post('/context', { profile_id: option.profile_id, trainer_id: option.trainer_id }, { preserveScroll: true });
    }
}
</script>

<template>
    <div class="flex flex-col gap-1">
        <label for="context-switcher" class="text-eyebrow font-semibold tracking-wider text-ink-subtle uppercase">Current context</label>
        <select
            id="context-switcher"
            :value="context.current ?? ''"
            class="h-10 w-full rounded-sm border border-border-soft bg-page px-3 text-body text-ink focus:border-brand focus:outline-none"
            @change="change"
        >
            <option v-if="!context.current" value="" disabled>No trainers yet</option>
            <optgroup v-for="group in context.groups" :key="group.title" :label="group.title">
                <option v-for="option in group.options" :key="option.key" :value="option.key">{{ option.label }}</option>
            </optgroup>
        </select>
    </div>
</template>
