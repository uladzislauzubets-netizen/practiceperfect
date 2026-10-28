<script setup lang="ts">
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { formatDateTime } from '@/lib/format';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{ url: string; uses_count: number; recent_uses: string[] }>();

const copied = ref(false);

async function copy(url: string) {
    await navigator.clipboard.writeText(url);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
}
</script>

<template>
    <Head title="ShareLink" />
    <PageHeader title="Player ShareLink" eyebrow="Trainer" subtitle="One link for all your players and parents. No expiry, unlimited uses." />

    <div class="grid gap-6 lg:grid-cols-2">
        <Card title="Your link">
            <div class="flex flex-wrap items-center gap-3">
                <code class="min-w-0 flex-1 truncate rounded-sm border border-border-soft bg-page px-3 py-2 text-body">{{ url }}</code>
                <Button variant="secondary" @click="copy(url)">{{ copied ? 'Copied' : 'Copy link' }}</Button>
            </div>
            <p class="mt-4 text-body text-ink-muted">New players register through this link. Players who already have an account are connected to you without a second account.</p>
        </Card>

        <Card title="Usage" eyebrow="Analytics">
            <p class="text-hero font-bold">{{ uses_count }}</p>
            <p class="mb-4 text-caption text-ink-subtle">times used</p>
            <ul v-if="recent_uses.length" class="flex flex-col gap-1.5">
                <li v-for="use in recent_uses" :key="use" class="text-body text-ink-muted">{{ formatDateTime(use) }}</li>
            </ul>
        </Card>
    </div>
</template>
