<script setup lang="ts">
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { formatDateTime } from '@/lib/format';
import { Head } from '@inertiajs/vue3';

interface Request {
    id: number;
    description: string;
    amount_label: string;
    status: string;
    status_label: string;
    requested_at: string;
    parent_notes: string | null;
    info_request_message: string | null;
}

defineProps<{ requests: Request[] }>();

const tone = (status: string) => ({ pending: 'warning', approved: 'success', denied: 'danger', expired: 'neutral' })[status] as 'warning' | 'success' | 'danger' | 'neutral';
</script>

<template>
    <Head title="My requests" />
    <PageHeader title="My requests" eyebrow="Player" subtitle="Purchases your parent needs to approve." />

    <EmptyState v-if="requests.length === 0" title="No requests yet" message="When you ask to buy something, you can follow it here." />

    <ul v-else class="grid gap-4">
        <li v-for="request in requests" :key="request.id" class="rounded-md border border-border-soft bg-surface p-5 shadow-card-soft">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-card font-semibold">{{ request.description }}</h2>
                    <p class="text-body text-ink-muted">{{ request.amount_label }} · {{ formatDateTime(request.requested_at) }}</p>
                    <p v-if="request.parent_notes" class="mt-2 text-body text-ink-muted">Note from your parent: {{ request.parent_notes }}</p>
                    <p v-if="request.info_request_message" class="mt-2 text-body text-warning">Your parent asked: {{ request.info_request_message }}</p>
                </div>
                <Badge :tone="tone(request.status)">{{ request.status_label }}</Badge>
            </div>
        </li>
    </ul>
</template>
