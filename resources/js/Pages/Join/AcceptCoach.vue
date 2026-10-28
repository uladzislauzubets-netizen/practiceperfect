<script setup lang="ts">
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import type { Branding } from '@/types';
import { fieldError } from '@/lib/format';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    link: { code: string; trainer_name: string; message: string | null };
    branding: Branding;
    blocked: boolean;
}>();

const form = useForm({});
</script>

<template>
    <Head :title="`Coach for ${link.trainer_name}`" />
    <Card :title="`Coach for ${link.trainer_name}`" eyebrow="Coach invitation">
        <div class="flex flex-col gap-4">
            <p v-if="link.message" class="rounded-sm border border-border-soft bg-page px-4 py-3 text-body text-ink-muted">{{ link.message }}</p>
            <p v-if="blocked" class="rounded-sm border border-danger/40 bg-danger/10 px-4 py-3 text-body text-danger" role="alert">
                You are already an active coach for another trainer. Coaches can only work for one trainer at a time.
            </p>
            <p v-if="fieldError(form.errors, 'link')" class="text-caption text-danger" role="alert">{{ fieldError(form.errors, 'link') }}</p>
            <Button :loading="form.processing" :disabled="blocked" @click="form.post(`/join/${link.code}/accept`)">Accept invitation</Button>
        </div>
    </Card>
</template>
