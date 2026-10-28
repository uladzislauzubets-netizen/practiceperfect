<script setup lang="ts">
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import FormField from '@/Components/FormField.vue';
import TextInput from '@/Components/TextInput.vue';
import type { Branding } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    link: { code: string; trainer_name: string; invitee_name: string | null; email: string | null; message: string | null };
    branding: Branding;
    account_exists: boolean;
}>();

const [firstName = '', ...rest] = (props.link.invitee_name ?? '').split(' ');

const form = useForm({
    first_name: firstName,
    last_name: rest.join(' '),
    email: props.link.email ?? '',
    phone: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(`/join/${props.link.code}/register-coach`, { onFinish: () => form.reset('password', 'password_confirmation') });
}
</script>

<template>
    <Head :title="`Coach for ${link.trainer_name}`" />
    <Card :title="`Coach for ${link.trainer_name}`" eyebrow="Coach invitation">
        <div v-if="link.message" class="mb-5 rounded-sm border border-border-soft bg-page px-4 py-3 text-body text-ink-muted">
            <p class="mb-1 text-caption font-semibold text-ink">Message from {{ link.trainer_name }}</p>
            {{ link.message }}
        </div>

        <div v-if="account_exists" class="flex flex-col gap-4">
            <p class="text-body text-ink-muted">An account already exists for {{ link.email }}. Log in to accept this invitation.</p>
            <Button href="/login">Log in</Button>
        </div>

        <form v-else class="flex flex-col gap-4" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField label="First name" for="first_name" :error="form.errors.first_name">
                    <TextInput id="first_name" v-model="form.first_name" required :invalid="!!form.errors.first_name" />
                </FormField>
                <FormField label="Last name" for="last_name" :error="form.errors.last_name">
                    <TextInput id="last_name" v-model="form.last_name" required :invalid="!!form.errors.last_name" />
                </FormField>
            </div>
            <FormField label="Email" for="email" hint="The invitation was sent to this address." :error="form.errors.email">
                <TextInput id="email" v-model="form.email" type="email" readonly required :invalid="!!form.errors.email" />
            </FormField>
            <FormField label="Phone" for="phone" :error="form.errors.phone">
                <TextInput id="phone" v-model="form.phone" type="tel" required :invalid="!!form.errors.phone" />
            </FormField>
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField label="Password" for="password" hint="At least 8 characters with letters and numbers." :error="form.errors.password">
                    <TextInput id="password" v-model="form.password" type="password" autocomplete="new-password" required :invalid="!!form.errors.password" />
                </FormField>
                <FormField label="Confirm password" for="password_confirmation">
                    <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" required />
                </FormField>
            </div>
            <Button type="submit" :loading="form.processing">Accept invitation</Button>
            <p class="text-center text-caption text-ink-muted">
                Already a coach on this platform? <Link href="/login" class="text-brand-soft underline">Log in</Link> instead.
            </p>
        </form>
    </Card>
</template>
