<script setup lang="ts">
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import FormField from '@/Components/FormField.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({ email: '', password: '' });

function submit() {
    form.post('/login', { onFinish: () => form.reset('password') });
}
</script>

<template>
    <Head title="Sign in" />
    <Card title="Sign in" eyebrow="Welcome back">
        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <FormField label="Email" for="email" :error="form.errors.email">
                <TextInput id="email" v-model="form.email" type="email" autocomplete="username" required autofocus :invalid="!!form.errors.email" />
            </FormField>
            <FormField label="Password" for="password" :error="form.errors.password">
                <TextInput id="password" v-model="form.password" type="password" autocomplete="current-password" required :invalid="!!form.errors.password" />
            </FormField>
            <Button type="submit" :loading="form.processing">Sign in</Button>
            <Link href="/forgot-password" class="text-center text-caption text-ink-muted hover:text-brand">Forgot your password?</Link>
        </form>
    </Card>
</template>
