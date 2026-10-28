<script setup lang="ts">
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import Checkbox from '@/Components/Checkbox.vue';
import type { Branding } from '@/types';
import { fieldError } from '@/lib/format';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    link: { code: string; trainer_name: string };
    branding: Branding;
    profiles: { id: number; name: string; is_self: boolean; already_connected: boolean }[];
}>();

const form = useForm({ profile_ids: props.profiles.filter((profile) => !profile.already_connected).map((profile) => profile.id) as number[] });
</script>

<template>
    <Head :title="`Join ${link.trainer_name}`" />
    <Card :title="`Who will train with ${link.trainer_name}?`" eyebrow="Choose family members">
        <form class="flex flex-col gap-4" @submit.prevent="form.post(`/join/${link.code}/associate`)">
            <p v-if="form.errors.profile_ids" class="text-caption text-danger" role="alert">{{ form.errors.profile_ids }}</p>
            <p v-if="fieldError(form.errors, 'link')" class="text-caption text-danger" role="alert">{{ fieldError(form.errors, 'link') }}</p>
            <div class="flex flex-col gap-3 rounded-sm border border-border-soft bg-page p-4">
                <Checkbox v-for="profile in profiles" :key="profile.id" v-model="form.profile_ids" :value="profile.id">
                    {{ profile.is_self ? `${profile.name} (Me)` : profile.name }}
                    <span v-if="profile.already_connected" class="text-caption text-ink-subtle"> already connected</span>
                </Checkbox>
            </div>
            <Button type="submit" :loading="form.processing" :disabled="form.profile_ids.length === 0">Connect selected</Button>
        </form>
    </Card>
</template>
