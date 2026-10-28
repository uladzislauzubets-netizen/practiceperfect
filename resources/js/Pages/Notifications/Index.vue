<script setup lang="ts">
import Button from '@/Components/Button.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatDateTime } from '@/lib/format';
import type { Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps<{ notifications: Paginated<{ id: string; title: string; read: boolean; created_at: string }> }>();
</script>

<template>
    <Head title="Notifications" />
    <PageHeader title="Notifications">
        <Button variant="secondary" size="sm" @click="router.post('/notifications/read-all')">Mark all as read</Button>
    </PageHeader>

    <EmptyState v-if="notifications.data.length === 0" title="Nothing here yet" message="Requests and updates will appear here." />

    <ul v-else class="flex flex-col gap-2">
        <li v-for="notification in notifications.data" :key="notification.id">
            <Link
                :href="`/notifications/${notification.id}/open`"
                class="flex items-center justify-between gap-4 rounded-sm border border-border-soft bg-surface px-4 py-3 hover:border-brand"
            >
                <span :class="notification.read ? 'text-ink-muted' : 'font-semibold'">{{ notification.title }}</span>
                <span class="shrink-0 text-caption text-ink-subtle">{{ formatDateTime(notification.created_at) }}</span>
            </Link>
        </li>
    </ul>

    <Pagination :paginator="notifications" />
</template>
