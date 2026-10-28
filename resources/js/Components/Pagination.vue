<script setup lang="ts">
import type { Paginated } from '@/types';
import { Link } from '@inertiajs/vue3';

defineProps<{ paginator: Paginated<unknown> }>();
</script>

<template>
    <nav v-if="paginator.last_page > 1" class="flex flex-wrap items-center justify-between gap-3 pt-4" aria-label="Pagination">
        <p class="text-caption text-ink-subtle">Showing {{ paginator.from }} to {{ paginator.to }} of {{ paginator.total }}</p>
        <div class="flex flex-wrap gap-1">
            <template v-for="(link, index) in paginator.links" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    class="rounded-xs px-3 py-1.5 text-caption font-semibold"
                    :class="link.active ? 'bg-brand text-white' : 'bg-surface-raised text-ink-muted hover:text-ink'"
                    v-html="link.label"
                />
                <span v-else class="rounded-xs px-3 py-1.5 text-caption text-ink-subtle/50" v-html="link.label" />
            </template>
        </div>
    </nav>
</template>
