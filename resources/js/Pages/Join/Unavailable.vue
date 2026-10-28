<script setup lang="ts">
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import type { Branding } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{ message: string; branding: Branding }>();
</script>

<template>
    <Head title="Link unavailable" />
    <Card title="This link cannot be used" eyebrow="Link unavailable">
        <div class="flex flex-col gap-4">
            <p class="text-body-lg">{{ message }}</p>
            <Button href="/dashboard" variant="secondary">Go to the platform</Button>
        </div>
    </Card>
</template>
