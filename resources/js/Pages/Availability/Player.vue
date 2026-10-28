<script setup lang="ts">
import AvailabilityEditor from '@/Components/AvailabilityEditor.vue';
import Button from '@/Components/Button.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PageHeader from '@/Components/PageHeader.vue';
import type { Slot } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{ context: { player: string; trainer: string } | null; slots: Slot[] }>();

const form = useForm({ slots: props.slots });
</script>

<template>
    <Head title="Availability" />
    <PageHeader
        title="Availability"
        eyebrow="Best Times"
        :subtitle="context ? `When ${context.player} can attend sessions with ${context.trainer}. Switch context to set times for someone else.` : undefined"
    />

    <EmptyState v-if="!context" title="Connect to a trainer first" message="Availability is set per trainer. Open a trainer's ShareLink to get started." />

    <form v-else :key="`${context.player}-${context.trainer}`" class="flex max-w-3xl flex-col gap-6" @submit.prevent="form.put('/availability', { preserveScroll: true })">
        <AvailabilityEditor v-model="form.slots" :errors="form.errors.slots" />
        <div><Button type="submit" :loading="form.processing">Save availability</Button></div>
    </form>
</template>
