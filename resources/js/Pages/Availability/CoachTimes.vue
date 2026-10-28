<script setup lang="ts">
import AvailabilityEditor from '@/Components/AvailabilityEditor.vue';
import Button from '@/Components/Button.vue';
import PageHeader from '@/Components/PageHeader.vue';
import type { Slot } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{ slots: Slot[] }>();

const form = useForm({ slots: props.slots });
</script>

<template>
    <Head title="My Times" />
    <PageHeader title="My Times" eyebrow="Coach" subtitle="Your recurring weekly availability. Add several time ranges per day if you need to." />

    <form class="flex max-w-3xl flex-col gap-6" @submit.prevent="form.put('/coach/my-times', { preserveScroll: true })">
        <AvailabilityEditor v-model="form.slots" :errors="form.errors.slots" />
        <div><Button type="submit" :loading="form.processing">Save my times</Button></div>
    </form>
</template>
