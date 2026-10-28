<script setup lang="ts">
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import FormField from '@/Components/FormField.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{ name: string }>();

const form = useForm({ password: '', password_confirmation: '' });

function submit() {
    form.post(window.location.href, { onFinish: () => form.reset() });
}
</script>

<template>
    <Head title="Set up your account" />
    <Card :title="`Welcome, ${name}`" eyebrow="Set up your account">
        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <p class="text-body text-ink-muted">Choose a password to finish setting up your trainer account.</p>
            <FormField label="Password" for="password" hint="At least 8 characters with letters and numbers." :error="form.errors.password">
                <TextInput id="password" v-model="form.password" type="password" autocomplete="new-password" required autofocus :invalid="!!form.errors.password" />
            </FormField>
            <FormField label="Confirm password" for="password_confirmation">
                <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" required />
            </FormField>
            <Button type="submit" :loading="form.processing">Finish setup</Button>
        </form>
    </Card>
</template>
