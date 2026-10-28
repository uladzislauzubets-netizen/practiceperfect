<script setup lang="ts">
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import Checkbox from '@/Components/Checkbox.vue';
import FormField from '@/Components/FormField.vue';
import PageHeader from '@/Components/PageHeader.vue';
import TextArea from '@/Components/TextArea.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface EditableUser {
    id: number;
    role: string;
    role_label: string;
    status_label: string;
    first_name: string;
    last_name: string;
    email: string;
    phone: string | null;
    school: string | null;
    business_name: string | null;
    address: string | null;
    website: string | null;
    description: string | null;
    bio: string | null;
    credentials: string | null;
    certifications: string | null;
    is_public: boolean;
}

const props = defineProps<{ user: EditableUser }>();

const form = useForm({
    first_name: props.user.first_name,
    last_name: props.user.last_name,
    email: props.user.email,
    phone: props.user.phone ?? '',
    school: props.user.school ?? '',
    business_name: props.user.business_name ?? '',
    address: props.user.address ?? '',
    website: props.user.website ?? '',
    description: props.user.description ?? '',
    bio: props.user.bio ?? '',
    credentials: props.user.credentials ?? '',
    certifications: props.user.certifications ?? '',
    is_public: props.user.is_public,
});
</script>

<template>
    <Head title="Edit user" />
    <PageHeader :title="`Edit ${user.first_name} ${user.last_name}`" eyebrow="Super Admin" :subtitle="`${user.role_label} · ${user.status_label}`" />

    <form class="flex max-w-3xl flex-col gap-6" @submit.prevent="form.patch(`/admin/users/${user.id}`)">
        <Card title="Account">
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField label="First name" for="first_name" :error="form.errors.first_name">
                    <TextInput id="first_name" v-model="form.first_name" required :invalid="!!form.errors.first_name" />
                </FormField>
                <FormField label="Last name" for="last_name" :error="form.errors.last_name">
                    <TextInput id="last_name" v-model="form.last_name" required :invalid="!!form.errors.last_name" />
                </FormField>
                <FormField label="Email" for="email" :error="form.errors.email">
                    <TextInput id="email" v-model="form.email" type="email" required :invalid="!!form.errors.email" />
                </FormField>
                <FormField label="Phone" for="phone" :error="form.errors.phone">
                    <TextInput id="phone" v-model="form.phone" type="tel" :invalid="!!form.errors.phone" />
                </FormField>
                <FormField label="School or organization" for="school" :error="form.errors.school">
                    <TextInput id="school" v-model="form.school" />
                </FormField>
            </div>
        </Card>

        <Card v-if="user.role === 'trainer'" title="Organization">
            <div class="grid gap-4">
                <FormField label="Business name" for="business_name" :error="form.errors.business_name">
                    <TextInput id="business_name" v-model="form.business_name" required :invalid="!!form.errors.business_name" />
                </FormField>
                <FormField label="Address" for="address" :error="form.errors.address"><TextInput id="address" v-model="form.address" /></FormField>
                <FormField label="Website" for="website" :error="form.errors.website"><TextInput id="website" v-model="form.website" type="url" /></FormField>
                <FormField label="Description" for="description" :error="form.errors.description"><TextArea id="description" v-model="form.description" /></FormField>
            </div>
        </Card>

        <Card v-if="user.role === 'coach'" title="Coach profile">
            <div class="grid gap-4">
                <FormField label="Bio" for="bio" :error="form.errors.bio"><TextArea id="bio" v-model="form.bio" /></FormField>
                <FormField label="Credentials" for="credentials" :error="form.errors.credentials"><TextArea id="credentials" v-model="form.credentials" /></FormField>
                <FormField label="Certifications" for="certifications" :error="form.errors.certifications"><TextArea id="certifications" v-model="form.certifications" /></FormField>
                <Checkbox v-model="form.is_public" label="Public profile" />
            </div>
        </Card>

        <div class="flex gap-3">
            <Button type="submit" :loading="form.processing">Save changes</Button>
            <Link href="/admin/users" class="inline-flex items-center px-4 text-body text-ink-muted hover:text-ink">Cancel</Link>
        </div>
    </form>
</template>
