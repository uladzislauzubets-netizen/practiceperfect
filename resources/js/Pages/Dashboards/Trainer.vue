<script setup lang="ts">
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatTile from '@/Components/StatTile.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    stats: { players: number; coaches: number; pending_invitations: number };
    share_link_url: string;
    best_windows: { label: string; players: number }[];
}>();

const copied = ref(false);

async function copy(url: string) {
    await navigator.clipboard.writeText(url);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
}
</script>

<template>
    <Head title="Dashboard" />
    <PageHeader title="Your organization" eyebrow="Trainer" subtitle="Players, coaches and invitations in one place.">
        <Button href="/trainer/coaches" variant="secondary">Invite a coach</Button>
    </PageHeader>

    <div class="grid gap-4 sm:grid-cols-3">
        <StatTile label="Players" :value="stats.players" href="/trainer/players" />
        <StatTile label="Coaches" :value="stats.coaches" href="/trainer/coaches" />
        <StatTile label="Pending coach invitations" :value="stats.pending_invitations" href="/trainer/coaches" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <Card title="Player ShareLink" eyebrow="Invite players">
            <p class="mb-3 text-body text-ink-muted">Share this link with players and parents. It never expires and can be used by anyone.</p>
            <div class="flex flex-wrap items-center gap-3">
                <code class="min-w-0 flex-1 truncate rounded-sm border border-border-soft bg-page px-3 py-2 text-caption">{{ share_link_url }}</code>
                <Button size="sm" variant="secondary" @click="copy(share_link_url)">{{ copied ? 'Copied' : 'Copy' }}</Button>
            </div>
        </Card>

        <Card title="Most popular times" eyebrow="Scheduling">
            <ul v-if="best_windows.length" class="flex flex-col gap-2">
                <li v-for="window in best_windows" :key="window.label" class="flex items-center justify-between rounded-sm bg-page px-4 py-2.5">
                    <span class="font-semibold">{{ window.label }}</span>
                    <span class="text-caption text-ink-muted">{{ window.players }} {{ window.players === 1 ? 'player' : 'players' }} available</span>
                </li>
            </ul>
            <p v-else class="text-body text-ink-muted">Player availability will show up here once your players set their Best Times.</p>
        </Card>
    </div>
</template>
