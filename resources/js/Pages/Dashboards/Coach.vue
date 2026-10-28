<script setup lang="ts">
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{ trainer_name: string | null; availability_set: boolean }>();
</script>

<template>
    <Head title="Dashboard" />
    <PageHeader title="Coach dashboard" eyebrow="Coach" :subtitle="trainer_name ? `You coach for ${trainer_name}.` : undefined" />

    <EmptyState v-if="!trainer_name" title="You are not active with a trainer" message="Ask your trainer to send you a new invitation link." />

    <Card v-else title="My Times" eyebrow="Availability">
        <p class="mb-4 text-body text-ink-muted">
            {{ availability_set ? 'Keep your weekly schedule up to date so your trainer can plan sessions around you.' : 'Tell your trainer when you can coach each week.' }}
        </p>
        <Button href="/coach/my-times">{{ availability_set ? 'Update my times' : 'Set my times' }}</Button>
    </Card>
</template>
