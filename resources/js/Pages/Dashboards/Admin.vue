<script setup lang="ts">
import Button from '@/Components/Button.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatTile from '@/Components/StatTile.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{ stats: { total: number; trainers: number; coaches: number; players: number; inactive: number } }>();
</script>

<template>
    <Head title="Dashboard" />
    <PageHeader title="Platform overview" eyebrow="Super Admin" subtitle="Everyone on the platform at a glance.">
        <Button href="/admin/users/create">Create trainer</Button>
    </PageHeader>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <StatTile label="Users" :value="stats.total" href="/admin/users" />
        <StatTile label="Active trainers" :value="stats.trainers" href="/admin/users?role=trainer" />
        <StatTile label="Active coaches" :value="stats.coaches" href="/admin/users?role=coach" />
        <StatTile label="Active players" :value="stats.players" href="/admin/users?role=player" />
        <StatTile label="Deactivated" :value="stats.inactive" href="/admin/users?status=inactive" />
    </div>
</template>
