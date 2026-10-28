<script setup lang="ts">
import Badge from '@/Components/Badge.vue';
import Button from '@/Components/Button.vue';
import EmptyState from '@/Components/EmptyState.vue';
import FormField from '@/Components/FormField.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import TextArea from '@/Components/TextArea.vue';
import { fieldError, formatDateTime } from '@/lib/format';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Approval {
    id: number;
    child: string;
    description: string;
    amount_label: string;
    payment_type: string;
    status: string;
    status_label: string;
    auto_approved: boolean;
    requested_at: string;
    expires_at: string;
    parent_notes: string | null;
    info_request_message: string | null;
}

defineProps<{ approvals: Approval[] }>();

const current = ref<{ approval: Approval; decision: 'approve' | 'deny' | 'request_info' } | null>(null);
const form = useForm({ decision: 'approve', notes: '' });

function open(approval: Approval, decision: 'approve' | 'deny' | 'request_info') {
    current.value = { approval, decision };
    form.decision = decision;
    form.notes = '';
    form.clearErrors();
}

function submit() {
    if (current.value) {
        form.post(`/family/approvals/${current.value.approval.id}`, { preserveScroll: true, onSuccess: () => (current.value = null) });
    }
}

const tone = (status: string) => ({ pending: 'warning', approved: 'success', denied: 'danger', expired: 'neutral' })[status] as 'warning' | 'success' | 'danger' | 'neutral';
const titles = { approve: 'Approve request', deny: 'Deny request', request_info: 'Ask your child for more information' };
</script>

<template>
    <Head title="Approvals" />
    <PageHeader title="Approvals" eyebrow="Player / Parent" subtitle="Purchases your children asked for. Requests expire after 48 hours." />

    <EmptyState v-if="approvals.length === 0" title="Nothing to approve" message="When a child asks to buy something, it shows up here." />

    <ul v-else class="grid gap-4">
        <li v-for="approval in approvals" :key="approval.id" class="rounded-md border border-border-soft bg-surface p-5 shadow-card-soft">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-card font-semibold">{{ approval.child }}: {{ approval.description }}</h2>
                    <p class="text-body text-ink-muted">{{ approval.amount_label }} · requested {{ formatDateTime(approval.requested_at) }}</p>
                    <p v-if="approval.status === 'pending'" class="text-caption text-ink-subtle">Expires {{ formatDateTime(approval.expires_at) }}</p>
                    <p v-if="approval.auto_approved" class="text-caption text-ink-subtle">Paid with tokens without approval, as you allowed.</p>
                    <p v-if="approval.parent_notes" class="mt-2 text-body text-ink-muted">Your note: {{ approval.parent_notes }}</p>
                    <p v-if="approval.info_request_message" class="mt-2 text-body text-ink-muted">You asked: {{ approval.info_request_message }}</p>
                </div>
                <Badge :tone="tone(approval.status)">{{ approval.status_label.replace('Pending Parent Approval', 'Pending') }}</Badge>
            </div>
            <div v-if="approval.status === 'pending'" class="mt-4 flex flex-wrap gap-2">
                <Button size="sm" @click="open(approval, 'approve')">Approve</Button>
                <Button size="sm" variant="danger" @click="open(approval, 'deny')">Deny</Button>
                <Button size="sm" variant="ghost" @click="open(approval, 'request_info')">Request more info</Button>
            </div>
        </li>
    </ul>

    <Modal :show="current !== null" :title="current ? titles[current.decision] : ''" size="sm" @close="current = null">
        <form class="flex flex-col gap-3" @submit.prevent="submit">
            <FormField :label="current?.decision === 'request_info' ? 'Your question' : 'Add a note (optional)'" for="notes" :error="form.errors.notes ?? fieldError(form.errors, 'approval')">
                <TextArea id="notes" v-model="form.notes" :required="current?.decision === 'request_info'" />
            </FormField>
        </form>
        <template #footer>
            <Button variant="ghost" @click="current = null">Cancel</Button>
            <Button :variant="current?.decision === 'deny' ? 'danger' : 'primary'" :loading="form.processing" @click="submit">Confirm</Button>
        </template>
    </Modal>
</template>
