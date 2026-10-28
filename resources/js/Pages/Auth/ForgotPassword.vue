<script setup lang="ts">
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import FormField from '@/Components/FormField.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({ email: '' });
</script>

<template>
    <Head title="Forgot password" />
    <Card title="Reset your password" eyebrow="Account help">
        <form class="flex flex-col gap-4" @submit.prevent="form.post('/forgot-password')">
            <p class="text-body text-ink-muted">Enter your email and we will send you a link to choose a new password. The link works for 1 hour.</p>
            <FormField label="Email" for="email" :error="form.errors.email">
                <TextInput id="email" v-model="form.email" type="email" autocomplete="username" required autofocus :invalid="!!form.errors.email" />
            </FormField>
            <Button type="submit" :loading="form.processing">Email me a reset link</Button>
            <Link href="/login" class="text-center text-caption text-ink-muted hover:text-brand">Back to sign in</Link>
        </form>
    </Card>
</template>
