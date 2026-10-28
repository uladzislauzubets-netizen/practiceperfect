<script setup lang="ts">
import Avatar from '@/Components/Avatar.vue';
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import Checkbox from '@/Components/Checkbox.vue';
import FormField from '@/Components/FormField.vue';
import PageHeader from '@/Components/PageHeader.vue';
import TextArea from '@/Components/TextArea.vue';
import TextInput from '@/Components/TextInput.vue';
import { formatDate } from '@/lib/format';
import type { SharedProps } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface ProfileData {
    first_name: string;
    last_name: string;
    phone: string | null;
    school: string | null;
    photo_url: string | null;
    avatar_url: string | null;
    email: string;
    role_label: string;
    created_at: string;
    business_name: string | null;
    address: string | null;
    website: string | null;
    description: string | null;
    bio: string | null;
    credentials: string | null;
    certifications: string | null;
    is_public: boolean;
    jersey_number: string | null;
    skill_level: string | null;
    has_player_profile: boolean;
    has_children: boolean;
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    emergency_contact_relationship: string | null;
}

const props = defineProps<{ limited: boolean; profile: ProfileData }>();

const role = computed(() => usePage<SharedProps>().props.auth?.role);

const form = useForm({
    first_name: props.profile.first_name,
    last_name: props.profile.last_name,
    phone: props.profile.phone ?? '',
    school: props.profile.school ?? '',
    photo: null as File | null,
    photo_url: props.profile.photo_url ?? '',
    remove_photo: false,
    business_name: props.profile.business_name ?? '',
    address: props.profile.address ?? '',
    website: props.profile.website ?? '',
    description: props.profile.description ?? '',
    bio: props.profile.bio ?? '',
    credentials: props.profile.credentials ?? '',
    certifications: props.profile.certifications ?? '',
    is_public: props.profile.is_public,
    jersey_number: props.profile.jersey_number ?? '',
    emergency_contact_name: props.profile.emergency_contact_name ?? '',
    emergency_contact_phone: props.profile.emergency_contact_phone ?? '',
    emergency_contact_relationship: props.profile.emergency_contact_relationship ?? '',
});

function pickPhoto(event: Event) {
    form.photo = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.remove_photo = false;
}

function submit() {
    form.post('/profile', { forceFormData: true, preserveScroll: true, onSuccess: () => (form.photo = null) });
}
</script>

<template>
    <Head title="Profile" />
    <PageHeader title="Profile" :subtitle="limited ? 'You can update your photo and a few basics. Ask your parent to change anything else.' : 'Keep your details current.'" />

    <form class="flex flex-col gap-6" @submit.prevent="submit">
        <Card title="Photo">
            <div class="flex flex-wrap items-center gap-5">
                <Avatar :name="`${profile.first_name} ${profile.last_name}`" :src="form.remove_photo ? null : profile.avatar_url" :size="72" />
                <div class="flex flex-col gap-3">
                    <FormField :error="form.errors.photo" hint="JPG, PNG or WebP up to 5MB. A thumbnail is created for you.">
                        <input type="file" accept="image/jpeg,image/png,image/webp" aria-label="Upload photo" class="text-caption text-ink-muted file:mr-3 file:rounded-xs file:border-0 file:bg-surface-raised file:px-3 file:py-1.5 file:text-ink" @change="pickPhoto" />
                    </FormField>
                    <Checkbox v-if="profile.avatar_url" v-model="form.remove_photo" label="Remove current photo" />
                </div>
            </div>
            <div v-if="!limited" class="mt-4">
                <FormField label="Or use a photo URL" for="photo_url" :error="form.errors.photo_url">
                    <TextInput id="photo_url" v-model="form.photo_url" type="url" placeholder="https://" :invalid="!!form.errors.photo_url" />
                </FormField>
            </div>
        </Card>

        <Card v-if="!limited" title="Basic information">
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField label="First name" for="first_name" :error="form.errors.first_name">
                    <TextInput id="first_name" v-model="form.first_name" required :invalid="!!form.errors.first_name" />
                </FormField>
                <FormField label="Last name" for="last_name" :error="form.errors.last_name">
                    <TextInput id="last_name" v-model="form.last_name" required :invalid="!!form.errors.last_name" />
                </FormField>
                <FormField label="Phone" for="phone" :error="form.errors.phone">
                    <TextInput id="phone" v-model="form.phone" type="tel" :invalid="!!form.errors.phone" />
                </FormField>
                <FormField v-if="role !== 'player'" label="School or organization" for="school" :error="form.errors.school">
                    <TextInput id="school" v-model="form.school" />
                </FormField>
            </div>
        </Card>

        <Card v-if="role === 'trainer'" title="Organization">
            <div class="grid gap-4">
                <FormField label="Business name" for="business_name" :error="form.errors.business_name">
                    <TextInput id="business_name" v-model="form.business_name" required :invalid="!!form.errors.business_name" />
                </FormField>
                <FormField label="Address" for="address" :error="form.errors.address">
                    <TextInput id="address" v-model="form.address" />
                </FormField>
                <FormField label="Website" for="website" :error="form.errors.website">
                    <TextInput id="website" v-model="form.website" type="url" placeholder="https://" :invalid="!!form.errors.website" />
                </FormField>
                <FormField label="Description" for="description" :error="form.errors.description">
                    <TextArea id="description" v-model="form.description" />
                </FormField>
            </div>
        </Card>

        <Card v-if="role === 'coach'" title="Coach profile">
            <div class="grid gap-4">
                <FormField label="Bio" for="bio" :error="form.errors.bio"><TextArea id="bio" v-model="form.bio" /></FormField>
                <FormField label="Credentials" for="credentials" :error="form.errors.credentials"><TextArea id="credentials" v-model="form.credentials" /></FormField>
                <FormField label="Certifications" for="certifications" :error="form.errors.certifications"><TextArea id="certifications" v-model="form.certifications" /></FormField>
                <Checkbox v-model="form.is_public" label="Show my profile publicly" />
            </div>
        </Card>

        <Card v-if="role === 'player' && (profile.has_player_profile || limited)" title="Player details">
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField label="School" for="player_school" :error="form.errors.school">
                    <TextInput id="player_school" v-model="form.school" />
                </FormField>
                <FormField label="Jersey number" for="jersey_number" :error="form.errors.jersey_number">
                    <TextInput id="jersey_number" v-model="form.jersey_number" maxlength="10" />
                </FormField>
            </div>
        </Card>

        <Card v-if="role === 'player' && !limited && (profile.has_children || profile.has_player_profile)" title="Emergency contact">
            <div class="grid gap-4 sm:grid-cols-3">
                <FormField label="Name" for="emergency_contact_name" :error="form.errors.emergency_contact_name">
                    <TextInput id="emergency_contact_name" v-model="form.emergency_contact_name" />
                </FormField>
                <FormField label="Phone" for="emergency_contact_phone" :error="form.errors.emergency_contact_phone">
                    <TextInput id="emergency_contact_phone" v-model="form.emergency_contact_phone" type="tel" :invalid="!!form.errors.emergency_contact_phone" />
                </FormField>
                <FormField label="Relationship" for="emergency_contact_relationship" :error="form.errors.emergency_contact_relationship">
                    <TextInput id="emergency_contact_relationship" v-model="form.emergency_contact_relationship" />
                </FormField>
            </div>
        </Card>

        <Card title="Account">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-caption font-semibold text-ink-muted">Email (used to sign in)</dt>
                    <dd class="text-body">{{ profile.email }}</dd>
                </div>
                <div>
                    <dt class="text-caption font-semibold text-ink-muted">Role</dt>
                    <dd class="text-body">{{ profile.role_label }}</dd>
                </div>
                <div v-if="role === 'player' && profile.has_player_profile">
                    <dt class="text-caption font-semibold text-ink-muted">Skill level (set by your trainer)</dt>
                    <dd class="text-body">{{ profile.skill_level ?? 'Not set yet' }}</dd>
                </div>
                <div>
                    <dt class="text-caption font-semibold text-ink-muted">Account created</dt>
                    <dd class="text-body">{{ formatDate(profile.created_at) }}</dd>
                </div>
            </dl>
        </Card>

        <div><Button type="submit" :loading="form.processing">Save profile</Button></div>
    </form>
</template>
