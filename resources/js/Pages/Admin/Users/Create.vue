<script setup lang="ts">
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import FormField from '@/Components/FormField.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import type { Option } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{ delivery_options: Option[] }>();

const form = useForm({
    role: 'trainer',
    business_name: '',
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    delivery: props.delivery_options[0]?.value ?? '',
});
</script>

<template>
    <Head title="Create user" />
    <PageHeader title="Create user" eyebrow="Super Admin" subtitle="Trainer accounts are created here only. There is no self registration." />

    <Card>
        <form class="flex max-w-2xl flex-col gap-4" @submit.prevent="form.post('/admin/users')">
            <FormField label="Role" for="role" :error="form.errors.role">
                <SelectInput id="role" v-model="form.role" :options="[{ value: 'trainer', label: 'Trainer' }]" />
            </FormField>
            <FormField label="Business name" for="business_name" :error="form.errors.business_name">
                <TextInput id="business_name" v-model="form.business_name" required :invalid="!!form.errors.business_name" />
            </FormField>
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField label="First name" for="first_name" :error="form.errors.first_name">
                    <TextInput id="first_name" v-model="form.first_name" required :invalid="!!form.errors.first_name" />
                </FormField>
                <FormField label="Last name" for="last_name" :error="form.errors.last_name">
                    <TextInput id="last_name" v-model="form.last_name" required :invalid="!!form.errors.last_name" />
                </FormField>
            </div>
            <FormField label="Email" for="email" :error="form.errors.email">
                <TextInput id="email" v-model="form.email" type="email" required :invalid="!!form.errors.email" />
            </FormField>
            <FormField label="Phone" for="phone" :error="form.errors.phone">
                <TextInput id="phone" v-model="form.phone" type="tel" required :invalid="!!form.errors.phone" />
            </FormField>
            <FormField label="How should the trainer get access?" for="delivery" :error="form.errors.delivery">
                <SelectInput id="delivery" v-model="form.delivery" :options="delivery_options" />
            </FormField>
            <div class="flex gap-3">
                <Button type="submit" :loading="form.processing">Create trainer</Button>
                <Link href="/admin/users" class="inline-flex items-center px-4 text-body text-ink-muted hover:text-ink">Cancel</Link>
            </div>
        </form>
    </Card>
</template>
