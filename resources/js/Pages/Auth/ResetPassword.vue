<script setup lang="ts">
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import FormField from '@/Components/FormField.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{ token: string; email: string | null }>();

const form = useForm({ token: props.token, email: props.email ?? '', password: '', password_confirmation: '' });

function submit() {
    form.post('/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') });
}
</script>

<template>
    <Head title="Choose a new password" />
    <Card title="Choose a new password" eyebrow="Account help">
        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <FormField label="Email" for="email" :error="form.errors.email">
                <TextInput id="email" v-model="form.email" type="email" autocomplete="username" required :invalid="!!form.errors.email" />
            </FormField>
            <FormField label="New password" for="password" hint="At least 8 characters with letters and numbers." :error="form.errors.password">
                <TextInput id="password" v-model="form.password" type="password" autocomplete="new-password" required :invalid="!!form.errors.password" />
            </FormField>
            <FormField label="Confirm new password" for="password_confirmation">
                <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" required />
            </FormField>
            <Button type="submit" :loading="form.processing">Reset password</Button>
        </form>
    </Card>
</template>
