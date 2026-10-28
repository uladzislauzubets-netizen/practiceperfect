<script setup lang="ts">
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import FormField from '@/Components/FormField.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ current_password: '', password: '', password_confirmation: '' });

function submit() {
    form.post('/password/change', { onFinish: () => form.reset() });
}
</script>

<template>
    <Head title="Change your password" />
    <Card title="Choose your own password" eyebrow="First sign in">
        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <p class="text-body text-ink-muted">You signed in with a temporary password. Pick a new one to continue.</p>
            <FormField label="Temporary password" for="current_password" :error="form.errors.current_password">
                <TextInput id="current_password" v-model="form.current_password" type="password" autocomplete="current-password" required :invalid="!!form.errors.current_password" />
            </FormField>
            <FormField label="New password" for="password" hint="At least 8 characters with letters and numbers." :error="form.errors.password">
                <TextInput id="password" v-model="form.password" type="password" autocomplete="new-password" required :invalid="!!form.errors.password" />
            </FormField>
            <FormField label="Confirm new password" for="password_confirmation">
                <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" required />
            </FormField>
            <Button type="submit" :loading="form.processing">Save password</Button>
        </form>
    </Card>
</template>
