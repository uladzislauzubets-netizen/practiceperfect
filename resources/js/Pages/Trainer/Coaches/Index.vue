<script setup lang="ts">
import Badge from '@/Components/Badge.vue';
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import FormField from '@/Components/FormField.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextArea from '@/Components/TextArea.vue';
import TextInput from '@/Components/TextInput.vue';
import { formatDate, postJson } from '@/lib/format';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

interface Coach {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    joined_at: string | null;
    times: string;
}

interface Invitation {
    id: number;
    email: string;
    name: string | null;
    status: 'pending' | 'accepted' | 'expired';
    expires_at: string | null;
    created_at: string;
}

const props = defineProps<{ coaches: Coach[]; invitations: Invitation[] }>();

const inviteForm = useForm({ email: '', name: '', message: '' });
const removing = ref<Coach | null>(null);

const check = reactive({ coach_id: '', starts_at: '', ends_at: '', event_id: '', reason: '' });
const checkState = ref<{ available: boolean; message: string | null } | null>(null);
const checkError = ref<string | null>(null);
const overrideErrors = ref<Record<string, string[]>>({});
const overrideSaved = ref(false);
const checking = ref(false);

const coachOptions = computed(() => props.coaches.map((coach) => ({ value: String(coach.id), label: coach.name })));

function invite() {
    inviteForm.post('/trainer/coaches/invitations', { preserveScroll: true, onSuccess: () => inviteForm.reset() });
}

function remove() {
    if (removing.value) {
        router.delete(`/trainer/coaches/${removing.value.id}`, { preserveScroll: true, onFinish: () => (removing.value = null) });
    }
}

async function runCheck() {
    checkError.value = null;
    checkState.value = null;
    overrideSaved.value = false;
    checking.value = true;

    const result = await postJson<{ available?: boolean; message?: string | null; errors?: Record<string, string[]> }>('/trainer/coach-assignments/check', {
        coach_id: Number(check.coach_id),
        starts_at: check.starts_at,
        ends_at: check.ends_at,
    });

    checking.value = false;

    if (!result.ok) {
        checkError.value = result.data.message ?? Object.values(result.data.errors ?? {}).flat()[0] ?? 'Could not check availability.';

        return;
    }

    checkState.value = { available: Boolean(result.data.available), message: result.data.message ?? null };
}

async function saveOverride() {
    overrideErrors.value = {};

    const result = await postJson<{ errors?: Record<string, string[]> }>('/trainer/coach-assignments/override', {
        coach_id: Number(check.coach_id),
        event_id: Number(check.event_id),
        reason: check.reason,
    });

    if (!result.ok) {
        overrideErrors.value = result.data.errors ?? {};

        return;
    }

    overrideSaved.value = true;
    check.reason = '';
}

const statusTone = { pending: 'warning', accepted: 'success', expired: 'danger' } as const;
</script>

<template>
    <Head title="Coaches" />
    <PageHeader title="Coaches" eyebrow="Trainer" subtitle="Coaches can only work for one trainer at a time." />

    <div class="grid gap-6">
        <Card title="Invite a coach" eyebrow="One time link, 7 days">
            <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="invite">
                <FormField label="Coach email" for="invite_email" :error="inviteForm.errors.email">
                    <TextInput id="invite_email" v-model="inviteForm.email" type="email" required :invalid="!!inviteForm.errors.email" />
                </FormField>
                <FormField label="Name (optional)" for="invite_name" :error="inviteForm.errors.name">
                    <TextInput id="invite_name" v-model="inviteForm.name" />
                </FormField>
                <FormField class="sm:col-span-2" label="Message (optional)" for="invite_message" :error="inviteForm.errors.message">
                    <TextArea id="invite_message" v-model="inviteForm.message" />
                </FormField>
                <div class="sm:col-span-2"><Button type="submit" :loading="inviteForm.processing">Send invitation</Button></div>
            </form>
        </Card>

        <Card v-if="invitations.length" title="Invitations">
            <ul class="flex flex-col gap-2">
                <li v-for="invitation in invitations" :key="invitation.id" class="flex flex-wrap items-center justify-between gap-3 rounded-sm bg-page px-4 py-3">
                    <div>
                        <p class="font-semibold">{{ invitation.name || invitation.email }}</p>
                        <p class="text-caption text-ink-subtle">{{ invitation.email }} · sent {{ formatDate(invitation.created_at) }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Badge :tone="statusTone[invitation.status]" class="capitalize">{{ invitation.status }}</Badge>
                        <Button v-if="invitation.status !== 'accepted'" size="sm" variant="secondary" @click="router.post(`/trainer/coaches/invitations/${invitation.id}/resend`, {}, { preserveScroll: true })">
                            Resend
                        </Button>
                    </div>
                </li>
            </ul>
        </Card>

        <EmptyState v-if="coaches.length === 0" title="No coaches yet" message="Invited coaches appear here once they accept." />

        <Card v-else title="Your coaches">
            <ul class="flex flex-col gap-3">
                <li v-for="coach in coaches" :key="coach.id" class="flex flex-wrap items-start justify-between gap-3 rounded-sm bg-page px-4 py-3">
                    <div>
                        <p class="font-semibold">{{ coach.name }}</p>
                        <p class="text-caption text-ink-subtle">{{ coach.email }}<span v-if="coach.phone"> · {{ coach.phone }}</span> · joined {{ formatDate(coach.joined_at) }}</p>
                        <p class="mt-1 text-body text-ink-muted">My Times: {{ coach.times || 'Not set yet' }}</p>
                    </div>
                    <Button size="sm" variant="ghost" @click="removing = coach">Remove</Button>
                </li>
            </ul>
        </Card>

        <Card v-if="coaches.length" title="Check a coach before assigning" eyebrow="Availability">
            <form class="grid gap-4 sm:grid-cols-3" @submit.prevent="runCheck">
                <FormField label="Coach" for="check_coach"><SelectInput id="check_coach" v-model="check.coach_id" :options="coachOptions" placeholder="Select coach" required /></FormField>
                <FormField label="Starts" for="check_start"><TextInput id="check_start" v-model="check.starts_at" type="datetime-local" required /></FormField>
                <FormField label="Ends" for="check_end"><TextInput id="check_end" v-model="check.ends_at" type="datetime-local" required /></FormField>
                <div class="sm:col-span-3"><Button type="submit" variant="secondary" :loading="checking">Check availability</Button></div>
            </form>

            <p v-if="checkError" class="mt-4 text-body text-danger" role="alert">{{ checkError }}</p>
            <p v-if="checkState?.available" class="mt-4 text-body text-brand-soft">This coach is available at that time.</p>

            <div v-if="checkState && !checkState.available" class="mt-4 flex flex-col gap-4 rounded-sm border border-warning/40 bg-warning/10 p-4">
                <p class="text-body text-warning" role="alert">{{ checkState.message }}</p>
                <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="saveOverride">
                    <FormField label="Event reference" for="event_id" :error="overrideErrors.event_id?.[0]"><TextInput id="event_id" v-model="check.event_id" type="number" min="1" required /></FormField>
                    <FormField label="Reason for override (required)" for="reason" :error="overrideErrors.reason?.[0]"><TextArea id="reason" v-model="check.reason" required /></FormField>
                    <div class="sm:col-span-2"><Button type="submit">Assign anyway</Button></div>
                </form>
                <p v-if="overrideSaved" class="text-body text-brand-soft">Override recorded.</p>
            </div>
        </Card>
    </div>

    <ConfirmModal
        v-if="removing"
        :show="true"
        title="Remove coach"
        :message="`Remove ${removing.name} from your organization?`"
        confirm-label="Remove"
        danger
        @close="removing = null"
        @confirm="remove"
    />
</template>
