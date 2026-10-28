<script setup lang="ts">
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatTile from '@/Components/StatTile.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    is_child: boolean;
    current: { profile_name: string; trainer_name: string; is_child_profile: boolean } | null;
    trainer_count: number;
    pending_approvals: number;
    my_pending_requests: number;
    has_profiles: boolean;
}>();
</script>

<template>
    <Head title="Dashboard" />
    <PageHeader
        :title="current ? current.trainer_name : 'Welcome'"
        :eyebrow="is_child ? 'Player' : 'Player / Parent'"
        :subtitle="current ? `Showing ${current.profile_name}'s training. Use the context switcher to change who or which trainer you are viewing.` : undefined"
    />

    <EmptyState
        v-if="!current"
        title="You are not connected to a trainer yet"
        :message="is_child ? 'Ask your parent to register you with a trainer.' : 'Open the ShareLink your trainer sent you to connect.'"
    >
        <Button v-if="!is_child && !has_profiles" href="/family">Set up your family</Button>
    </EmptyState>

    <div v-else class="grid gap-4 sm:grid-cols-3">
        <StatTile label="Trainers" :value="trainer_count" />
        <StatTile v-if="!is_child" label="Requests waiting for you" :value="pending_approvals" href="/family/approvals" />
        <StatTile v-else label="My pending requests" :value="my_pending_requests" href="/purchase-requests" />
    </div>

    <div v-if="current" class="mt-6">
        <Card title="Best Times" eyebrow="Availability">
            <p class="mb-4 text-body text-ink-muted">Tell {{ current.trainer_name }} when {{ current.profile_name }} can attend sessions.</p>
            <Button href="/availability">Set availability</Button>
        </Card>
    </div>
</template>
