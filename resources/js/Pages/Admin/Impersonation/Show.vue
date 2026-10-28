<script setup lang="ts">
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { formatDateTime, formatDuration } from '@/lib/format';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    log: { id: number; admin: string; impersonated: string; started_at: string; ended_at: string | null; duration_seconds: number | null };
    actions: { id: number; method: string; path: string; status_code: number; created_at: string }[];
}>();
</script>

<template>
    <Head title="Impersonation session" />
    <PageHeader
        :title="`${log.admin} as ${log.impersonated}`"
        eyebrow="Impersonation session"
        :subtitle="`${formatDateTime(log.started_at)} · ${formatDuration(log.duration_seconds)}`"
    >
        <Link href="/admin/impersonation-history" class="text-body text-ink-muted hover:text-ink">Back to history</Link>
    </PageHeader>

    <EmptyState v-if="actions.length === 0" title="No changes were made" message="Only actions that change data are logged." />

    <Card v-else title="Actions during this session">
        <ul class="flex flex-col gap-2">
            <li v-for="action in actions" :key="action.id" class="flex flex-wrap items-center justify-between gap-3 rounded-sm bg-page px-4 py-2.5 text-body">
                <span><span class="mr-2 font-semibold">{{ action.method }}</span>{{ action.path }}</span>
                <span class="text-caption text-ink-subtle">{{ action.status_code }} · {{ formatDateTime(action.created_at) }}</span>
            </li>
        </ul>
    </Card>
</template>
