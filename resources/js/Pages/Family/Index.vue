<script setup lang="ts">
import Avatar from '@/Components/Avatar.vue';
import Badge from '@/Components/Badge.vue';
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import FormField from '@/Components/FormField.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import Toggle from '@/Components/Toggle.vue';
import { fieldError, formatDate } from '@/lib/format';
import type { Option } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Trainer {
    id: number;
    name: string;
    connected_at: string;
}

interface Profile {
    id: number;
    name: string;
    first_name: string;
    last_name: string;
    birth_date: string | null;
    age: number | null;
    gender: string | null;
    school: string | null;
    jersey_number: string | null;
    is_child: boolean;
    is_self: boolean;
    avatar_url: string | null;
    has_login: boolean;
    login_email: string | null;
    allow_token_spending_without_approval: boolean;
    trainers: Trainer[];
}

const props = defineProps<{ profiles: Profile[]; trainers: { id: number; name: string }[]; has_self_profile: boolean }>();

const genders: Option[] = [
    { value: 'male', label: 'Male' },
    { value: 'female', label: 'Female' },
    { value: 'other', label: 'Other' },
];

type Dialog = 'add-child' | 'add-self' | 'edit' | 'add-trainer' | 'login';
const dialog = ref<Dialog | null>(null);
const target = ref<Profile | null>(null);
const removal = ref<{ profile: Profile; trainer: Trainer } | null>(null);

const childForm = useForm({
    first_name: '',
    last_name: '',
    birth_date: '',
    gender: '',
    school: '',
    photo: null as File | null,
    trainer_ids: [] as number[],
    confirm_duplicate: false,
});

const selfForm = useForm({ confirm_adult: false, gender: '', birth_date: '' });

const editForm = useForm({
    first_name: '',
    last_name: '',
    birth_date: '',
    gender: '',
    school: '',
    jersey_number: '',
    allow_token_spending_without_approval: false,
    photo: null as File | null,
});

const trainerForm = useForm({ share_code: '', trainer_id: '' as string | number });
const loginForm = useForm({ email: '', password: '', password_confirmation: '' });

const singleTrainer = computed(() => (props.trainers.length === 1 ? props.trainers[0] : null));
const hasDuplicateWarning = computed(() => !!fieldError(childForm.errors, 'duplicate'));

function open(kind: Dialog, profile: Profile | null = null) {
    target.value = profile;
    dialog.value = kind;

    if (kind === 'edit' && profile) {
        editForm.defaults({
            first_name: profile.first_name,
            last_name: profile.last_name,
            birth_date: profile.birth_date ?? '',
            gender: profile.gender ?? '',
            school: profile.school ?? '',
            jersey_number: profile.jersey_number ?? '',
            allow_token_spending_without_approval: profile.allow_token_spending_without_approval,
            photo: null,
        }).reset();
    }
}

function close() {
    dialog.value = null;
    target.value = null;
    childForm.reset();
    childForm.clearErrors();
    selfForm.reset();
    trainerForm.reset();
    trainerForm.clearErrors();
    loginForm.reset();
    loginForm.clearErrors();
}

function pickFile(form: { photo: File | null }, event: Event) {
    form.photo = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function submitChild() {
    childForm.post('/family/profiles', { forceFormData: true, preserveScroll: true, onSuccess: close });
}

function submitSelf() {
    selfForm.post('/family/self', { preserveScroll: true, onSuccess: close });
}

function submitEdit() {
    if (target.value) {
        editForm.post(`/family/profiles/${target.value.id}`, { forceFormData: true, preserveScroll: true, onSuccess: close });
    }
}

function submitTrainer() {
    if (target.value) {
        trainerForm
            .transform((data) => ({ share_code: data.share_code || null, trainer_id: data.share_code ? null : data.trainer_id || null }))
            .post(`/family/profiles/${target.value.id}/trainers`, { preserveScroll: true, onSuccess: close });
    }
}

function submitLogin() {
    if (target.value) {
        loginForm.post(`/family/profiles/${target.value.id}/login`, { preserveScroll: true, onSuccess: close });
    }
}

function confirmRemoval() {
    if (removal.value) {
        router.delete(`/family/profiles/${removal.value.profile.id}/trainers/${removal.value.trainer.id}`, {
            preserveScroll: true,
            onFinish: () => (removal.value = null),
        });
    }
}

const availableTrainers = computed(() =>
    target.value ? props.trainers.filter((trainer) => !target.value?.trainers.some((connected) => connected.id === trainer.id)) : [],
);
const trainerOptions = computed<Option[]>(() => availableTrainers.value.map((trainer) => ({ value: String(trainer.id), label: trainer.name })));
</script>

<template>
    <Head title="Family" />
    <PageHeader title="Family" eyebrow="Player / Parent" subtitle="Manage who trains and with which trainer.">
        <Button v-if="!has_self_profile" variant="secondary" @click="open('add-self')">Train myself</Button>
        <Button @click="open('add-child')">+ Add Child</Button>
    </PageHeader>

    <EmptyState v-if="profiles.length === 0" title="No player profiles yet" message="Add a child, or set yourself up as a player, then use a trainer's ShareLink to connect." />

    <div v-else class="grid gap-5">
        <article v-for="profile in profiles" :key="profile.id" class="rounded-md border border-border-soft bg-surface p-5 shadow-card-soft">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Avatar :name="profile.name" :src="profile.avatar_url" :size="52" />
                    <div>
                        <h2 class="text-card font-semibold">
                            {{ profile.name }}
                            <Badge v-if="profile.is_self" tone="success">Me</Badge>
                            <Badge v-else tone="info">Child</Badge>
                        </h2>
                        <p class="text-caption text-ink-subtle">
                            <span v-if="profile.age !== null">Age {{ profile.age }}</span>
                            <span v-if="profile.has_login"> · Login: {{ profile.login_email }}</span>
                        </p>
                    </div>
                </div>
                <div v-if="!profile.is_self" class="flex flex-wrap gap-2">
                    <Button size="sm" variant="secondary" @click="open('edit', profile)">Edit and settings</Button>
                    <Button v-if="!profile.has_login" size="sm" variant="ghost" @click="open('login', profile)">Create login</Button>
                </div>
            </div>

            <div class="mt-4 border-t border-border-soft pt-4">
                <div class="mb-2 flex items-center justify-between">
                    <p class="text-eyebrow font-semibold tracking-wider text-ink-subtle uppercase">Trainers</p>
                    <Button size="sm" variant="ghost" @click="open('add-trainer', profile)">Add Trainer</Button>
                </div>
                <p v-if="profile.trainers.length === 0" class="text-body text-ink-muted">Not connected to a trainer yet.</p>
                <ul v-else class="flex flex-col gap-2">
                    <li v-for="trainer in profile.trainers" :key="trainer.id" class="flex flex-wrap items-center justify-between gap-3 rounded-sm bg-page px-4 py-2.5">
                        <span><span class="font-semibold">{{ trainer.name }}</span> <span class="text-caption text-ink-subtle">since {{ formatDate(trainer.connected_at) }}</span></span>
                        <button type="button" class="text-caption font-semibold text-ink-subtle hover:text-danger" @click="removal = { profile, trainer }">Remove</button>
                    </li>
                </ul>
            </div>
        </article>
    </div>

    <Modal :show="dialog === 'add-child'" title="Add a child" @close="close">
        <form class="flex flex-col gap-4" @submit.prevent="submitChild">
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField label="First name" for="c_first" :error="childForm.errors.first_name"><TextInput id="c_first" v-model="childForm.first_name" required /></FormField>
                <FormField label="Last name" for="c_last" :error="childForm.errors.last_name"><TextInput id="c_last" v-model="childForm.last_name" required /></FormField>
                <FormField label="Date of birth" for="c_dob" hint="Children must be 1 to 18 years old." :error="childForm.errors.birth_date"><TextInput id="c_dob" v-model="childForm.birth_date" type="date" required /></FormField>
                <FormField label="Gender" for="c_gender" :error="childForm.errors.gender"><SelectInput id="c_gender" v-model="childForm.gender" :options="genders" placeholder="Select" required /></FormField>
                <FormField label="School (optional)" for="c_school" :error="childForm.errors.school"><TextInput id="c_school" v-model="childForm.school" /></FormField>
                <FormField label="Photo (optional)" for="c_photo" :error="childForm.errors.photo">
                    <input id="c_photo" type="file" accept="image/jpeg,image/png,image/webp" class="text-caption text-ink-muted file:mr-3 file:rounded-xs file:border-0 file:bg-surface-raised file:px-3 file:py-1.5 file:text-ink" @change="pickFile(childForm, $event)" />
                </FormField>
            </div>

            <fieldset v-if="trainers.length" class="rounded-sm border border-border-soft bg-page p-4">
                <legend class="px-1 text-caption font-semibold text-ink-muted">Trainers</legend>
                <div v-if="singleTrainer" class="flex flex-col gap-2">
                    <p class="text-body">Will {{ childForm.first_name || 'your child' }} also train with {{ singleTrainer.name }}?</p>
                    <div class="flex gap-5">
                        <label class="inline-flex items-center gap-2"><input type="radio" name="single-trainer" :checked="childForm.trainer_ids.includes(singleTrainer.id)" @change="childForm.trainer_ids = [singleTrainer.id]" /> Yes</label>
                        <label class="inline-flex items-center gap-2"><input type="radio" name="single-trainer" :checked="childForm.trainer_ids.length === 0" @change="childForm.trainer_ids = []" /> No</label>
                    </div>
                </div>
                <div v-else class="flex flex-col gap-2">
                    <p class="text-body text-ink-muted">Choose the trainers this child will train with.</p>
                    <Checkbox v-for="trainer in trainers" :key="trainer.id" v-model="childForm.trainer_ids" :value="trainer.id" :label="trainer.name" />
                </div>
                <p v-if="childForm.errors['trainer_ids.0']" class="mt-2 text-caption text-danger">{{ childForm.errors['trainer_ids.0'] }}</p>
            </fieldset>

            <div v-if="hasDuplicateWarning" class="rounded-sm border border-warning/40 bg-warning/10 p-3 text-body text-warning" role="alert">
                {{ fieldError(childForm.errors, 'duplicate') }}
                <Checkbox v-model="childForm.confirm_duplicate" label="Yes, add anyway" class="mt-2" />
            </div>
        </form>
        <template #footer>
            <Button variant="ghost" @click="close">Cancel</Button>
            <Button :loading="childForm.processing" @click="submitChild">Add child</Button>
        </template>
    </Modal>

    <Modal :show="dialog === 'add-self'" title="Train myself" size="sm" @close="close">
        <form class="flex flex-col gap-4" @submit.prevent="submitSelf">
            <FormField label="Gender (optional)" for="s_gender" :error="selfForm.errors.gender"><SelectInput id="s_gender" v-model="selfForm.gender" :options="genders" placeholder="Select" /></FormField>
            <FormField label="Date of birth (optional)" for="s_dob" :error="selfForm.errors.birth_date"><TextInput id="s_dob" v-model="selfForm.birth_date" type="date" /></FormField>
            <FormField :error="selfForm.errors.confirm_adult"><Checkbox v-model="selfForm.confirm_adult" label="I confirm I am 18 or older." /></FormField>
        </form>
        <template #footer>
            <Button variant="ghost" @click="close">Cancel</Button>
            <Button :loading="selfForm.processing" @click="submitSelf">Save</Button>
        </template>
    </Modal>

    <Modal :show="dialog === 'edit'" :title="`Edit ${target?.first_name ?? ''}`" @close="close">
        <form class="flex flex-col gap-4" @submit.prevent="submitEdit">
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField label="First name" for="e_first" :error="editForm.errors.first_name"><TextInput id="e_first" v-model="editForm.first_name" required /></FormField>
                <FormField label="Last name" for="e_last" :error="editForm.errors.last_name"><TextInput id="e_last" v-model="editForm.last_name" required /></FormField>
                <FormField label="Date of birth" for="e_dob" :error="editForm.errors.birth_date"><TextInput id="e_dob" v-model="editForm.birth_date" type="date" required /></FormField>
                <FormField label="Gender" for="e_gender" :error="editForm.errors.gender"><SelectInput id="e_gender" v-model="editForm.gender" :options="genders" required /></FormField>
                <FormField label="School" for="e_school" :error="editForm.errors.school"><TextInput id="e_school" v-model="editForm.school" /></FormField>
                <FormField label="Jersey number" for="e_jersey" :error="editForm.errors.jersey_number"><TextInput id="e_jersey" v-model="editForm.jersey_number" maxlength="10" /></FormField>
                <FormField label="New photo" for="e_photo" :error="editForm.errors.photo">
                    <input id="e_photo" type="file" accept="image/jpeg,image/png,image/webp" class="text-caption text-ink-muted file:mr-3 file:rounded-xs file:border-0 file:bg-surface-raised file:px-3 file:py-1.5 file:text-ink" @change="pickFile(editForm, $event)" />
                </FormField>
            </div>
            <div class="flex items-start justify-between gap-4 rounded-sm border border-border-soft bg-page p-4">
                <div>
                    <p class="font-semibold">Allow token spending without approval</p>
                    <p class="text-caption text-ink-subtle">Off by default. USD purchases always need your approval.</p>
                </div>
                <Toggle v-model="editForm.allow_token_spending_without_approval" label="Allow token spending without approval" />
            </div>
        </form>
        <template #footer>
            <Button variant="ghost" @click="close">Cancel</Button>
            <Button :loading="editForm.processing" @click="submitEdit">Save</Button>
        </template>
    </Modal>

    <Modal :show="dialog === 'add-trainer'" :title="`Add a trainer for ${target?.first_name ?? ''}`" size="sm" @close="close">
        <form class="flex flex-col gap-4" @submit.prevent="submitTrainer">
            <FormField label="Enter a ShareLink" for="t_code" :error="trainerForm.errors.share_code">
                <TextInput id="t_code" v-model="trainerForm.share_code" placeholder="https://.../join/abc123xyz0" />
            </FormField>
            <template v-if="trainerOptions.length">
                <p class="text-center text-caption text-ink-subtle">or</p>
                <FormField label="Choose from my trainers" for="t_trainer" :error="trainerForm.errors.trainer_id">
                    <SelectInput id="t_trainer" v-model="trainerForm.trainer_id" :options="trainerOptions" placeholder="Select a trainer" :disabled="!!trainerForm.share_code" />
                </FormField>
            </template>
        </form>
        <template #footer>
            <Button variant="ghost" @click="close">Cancel</Button>
            <Button :loading="trainerForm.processing" @click="submitTrainer">Confirm</Button>
        </template>
    </Modal>

    <Modal :show="dialog === 'login'" :title="`Create a login for ${target?.first_name ?? ''}`" size="sm" @close="close">
        <form class="flex flex-col gap-4" @submit.prevent="submitLogin">
            <p class="text-body text-ink-muted">They can view their training but need your approval for purchases, and cannot add trainers.</p>
            <FormField label="Login email" for="l_email" :error="loginForm.errors.email"><TextInput id="l_email" v-model="loginForm.email" type="email" required /></FormField>
            <FormField label="Password" for="l_password" hint="At least 8 characters with letters and numbers." :error="loginForm.errors.password"><TextInput id="l_password" v-model="loginForm.password" type="password" autocomplete="new-password" required /></FormField>
            <FormField label="Confirm password" for="l_password_confirmation"><TextInput id="l_password_confirmation" v-model="loginForm.password_confirmation" type="password" autocomplete="new-password" required /></FormField>
        </form>
        <template #footer>
            <Button variant="ghost" @click="close">Cancel</Button>
            <Button :loading="loginForm.processing" @click="submitLogin">Create login</Button>
        </template>
    </Modal>

    <ConfirmModal
        v-if="removal"
        :show="true"
        title="Remove from trainer"
        :message="`Remove ${removal.profile.first_name} from ${removal.trainer.name}? This will cancel all upcoming RSVPs.`"
        confirm-label="Remove"
        danger
        @close="removal = null"
        @confirm="confirmRemoval"
    />
</template>
