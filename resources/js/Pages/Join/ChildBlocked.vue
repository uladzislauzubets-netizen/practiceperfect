<script setup lang="ts">
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import type { Branding } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{ trainer_name: string; branding: Branding }>();
</script>

<template>
    <Head title="Ask your parent" />
    <Card :title="`Join ${trainer_name}`" eyebrow="Parent needed">
        <div class="flex flex-col gap-4">
            <p class="text-body-lg">Ask your parent to register you with this trainer.</p>
            <p class="text-body text-ink-muted">We emailed them a link so they can review and finish the registration for you.</p>
            <Button href="/dashboard" variant="secondary">Back to my dashboard</Button>
        </div>
    </Card>
</template>
