<script setup lang="ts">
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({});
</script>

<template>
    <Head title="Verify your email" />
    <Card title="Verify your email address" eyebrow="One more step">
        <div class="flex flex-col gap-4">
            <p class="text-body text-ink-muted">
                We sent a verification link to your email address. Open it to start using your account. The link works for 24 hours.
            </p>
            <Button :loading="form.processing" @click="form.post('/email/verification-notification')">Resend verification email</Button>
        </div>
    </Card>
</template>
