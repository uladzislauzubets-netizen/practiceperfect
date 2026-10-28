<script setup lang="ts">
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatDateTime, formatDuration } from '@/lib/format';
import type { Paginated } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    logs: Paginated<{
        id: number;
        admin: string;
        impersonated: string;
        impersonated_role: string;
        started_at: string;
        ended_at: string | null;
        duration_seconds: number | null;
        actions_count: number;
        ip_address: string | null;
    }>;
}>();
</script>

<template>
    <Head title="Impersonation history" />
    <PageHeader title="Impersonation history" eyebrow="Super Admin" subtitle="Who viewed the platform as whom, and for how long. Kept for compliance." />

    <EmptyState v-if="logs.data.length === 0" title="No impersonation yet" />

    <div v-else class="overflow-x-auto rounded-md border border-border-soft">
        <table class="w-full min-w-[820px] text-left text-body">
            <thead class="bg-surface text-caption text-ink-subtle uppercase">
                <tr>
                    <th scope="col" class="px-4 py-3">Admin</th>
                    <th scope="col" class="px-4 py-3">Impersonated</th>
                    <th scope="col" class="px-4 py-3">Started</th>
                    <th scope="col" class="px-4 py-3">Ended</th>
                    <th scope="col" class="px-4 py-3">Duration</th>
                    <th scope="col" class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="log in logs.data" :key="log.id" class="border-t border-border-soft">
                    <td class="px-4 py-3 font-semibold">{{ log.admin }}</td>
                    <td class="px-4 py-3">{{ log.impersonated }} <span class="text-caption text-ink-subtle">({{ log.impersonated_role }})</span></td>
                    <td class="px-4 py-3 text-ink-muted">{{ formatDateTime(log.started_at) }}</td>
                    <td class="px-4 py-3 text-ink-muted"><Badge v-if="!log.ended_at" tone="warning">Active</Badge><template v-else>{{ formatDateTime(log.ended_at) }}</template></td>
                    <td class="px-4 py-3">{{ formatDuration(log.duration_seconds) }}</td>
                    <td class="px-4 py-3"><Link :href="`/admin/impersonation-history/${log.id}`" class="text-brand-soft hover:underline">{{ log.actions_count }} logged</Link></td>
                </tr>
            </tbody>
        </table>
    </div>

    <Pagination :paginator="logs" />
</template>
