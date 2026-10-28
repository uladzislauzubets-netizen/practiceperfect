<script setup lang="ts">
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import Checkbox from '@/Components/Checkbox.vue';
import FormField from '@/Components/FormField.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import type { Branding, Option } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface ChildForm {
    first_name: string;
    last_name: string;
    birth_date: string;
    gender: string;
}

interface Prefill {
    first_name?: string;
    last_name?: string;
    email?: string;
    phone?: string;
    trains_self?: boolean;
    gender?: string;
    birth_date?: string;
    children?: ChildForm[];
}

const props = defineProps<{
    link: { code: string; trainer_name: string };
    prefill: Prefill | null;
    prefill_token: string | null;
    branding: Branding;
}>();

const genders: Option[] = [
    { value: 'male', label: 'Male' },
    { value: 'female', label: 'Female' },
    { value: 'other', label: 'Other' },
];

const form = useForm({
    first_name: props.prefill?.first_name ?? '',
    last_name: props.prefill?.last_name ?? '',
    email: props.prefill?.email ?? '',
    phone: props.prefill?.phone ?? '',
    password: '',
    password_confirmation: '',
    trains_self: props.prefill?.trains_self ?? true,
    confirm_adult: false,
    gender: props.prefill?.gender ?? '',
    birth_date: props.prefill?.birth_date ?? '',
    children: (props.prefill?.children ?? []) as ChildForm[],
    prefill: props.prefill_token ?? '',
});

function addChild() {
    form.children.push({ first_name: '', last_name: '', birth_date: '', gender: '' });
}

function submit() {
    form.post(`/join/${props.link.code}/register`, { onFinish: () => form.reset('password', 'password_confirmation') });
}

function childError(index: number, field: string): string | undefined {
    return (form.errors as Record<string, string>)[`children.${index}.${field}`];
}
</script>

<template>
    <Head :title="`Join ${link.trainer_name}`" />
    <Card :title="`Join ${link.trainer_name}`" eyebrow="You are invited">
        <form class="flex flex-col gap-6" @submit.prevent="submit">
            <p v-if="prefill" class="rounded-sm border border-brand/40 bg-brand/10 px-4 py-3 text-body text-brand-soft">
                We filled in your details from your form submission. Choose a password to finish.
            </p>

            <fieldset class="flex flex-col gap-4">
                <legend class="mb-2 text-eyebrow font-semibold tracking-wider text-ink-subtle uppercase">Your account</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField label="First name" for="first_name" :error="form.errors.first_name">
                        <TextInput id="first_name" v-model="form.first_name" autocomplete="given-name" required :invalid="!!form.errors.first_name" />
                    </FormField>
                    <FormField label="Last name" for="last_name" :error="form.errors.last_name">
                        <TextInput id="last_name" v-model="form.last_name" autocomplete="family-name" required :invalid="!!form.errors.last_name" />
                    </FormField>
                </div>
                <FormField label="Email" for="email" :error="form.errors.email">
                    <TextInput id="email" v-model="form.email" type="email" autocomplete="email" required :invalid="!!form.errors.email" />
                    <p v-if="form.errors.email?.includes('already exists')" class="text-caption">
                        <Link href="/login" class="text-brand-soft underline">Log in</Link> and open this link again.
                    </p>
                </FormField>
                <FormField label="Phone" for="phone" :error="form.errors.phone">
                    <TextInput id="phone" v-model="form.phone" type="tel" autocomplete="tel" required :invalid="!!form.errors.phone" />
                </FormField>
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField label="Password" for="password" hint="At least 8 characters with letters and numbers." :error="form.errors.password">
                        <TextInput id="password" v-model="form.password" type="password" autocomplete="new-password" required :invalid="!!form.errors.password" />
                    </FormField>
                    <FormField label="Confirm password" for="password_confirmation">
                        <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" required />
                    </FormField>
                </div>
            </fieldset>

            <fieldset class="flex flex-col gap-4">
                <legend class="mb-2 text-eyebrow font-semibold tracking-wider text-ink-subtle uppercase">Who will be training?</legend>
                <p v-if="form.errors.trains_self" class="text-caption text-danger" role="alert">{{ form.errors.trains_self }}</p>

                <div class="flex flex-col gap-4 rounded-sm border border-border-soft bg-page p-4">
                    <Checkbox v-model="form.trains_self" label="I will train myself" />
                    <template v-if="form.trains_self">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <FormField label="Gender" for="gender" :error="form.errors.gender">
                                <SelectInput id="gender" v-model="form.gender" :options="genders" placeholder="Select" :invalid="!!form.errors.gender" />
                            </FormField>
                            <FormField label="Date of birth (optional)" for="birth_date" :error="form.errors.birth_date">
                                <TextInput id="birth_date" v-model="form.birth_date" type="date" :invalid="!!form.errors.birth_date" />
                            </FormField>
                        </div>
                        <FormField :error="form.errors.confirm_adult">
                            <Checkbox v-model="form.confirm_adult" label="I confirm I am 18 or older. Players under 18 are managed by a parent." />
                        </FormField>
                    </template>
                </div>

                <div v-for="(child, index) in form.children" :key="index" class="flex flex-col gap-4 rounded-sm border border-border-soft bg-page p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-card font-semibold">Child {{ index + 1 }}</p>
                        <button type="button" class="text-caption text-ink-subtle hover:text-danger" @click="form.children.splice(index, 1)">Remove</button>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <FormField label="First name" :for="`child-first-${index}`" :error="childError(index, 'first_name')">
                            <TextInput :id="`child-first-${index}`" v-model="child.first_name" required />
                        </FormField>
                        <FormField label="Last name" :for="`child-last-${index}`" :error="childError(index, 'last_name')">
                            <TextInput :id="`child-last-${index}`" v-model="child.last_name" required />
                        </FormField>
                        <FormField label="Date of birth" :for="`child-dob-${index}`" hint="Children must be 1 to 18 years old." :error="childError(index, 'birth_date')">
                            <TextInput :id="`child-dob-${index}`" v-model="child.birth_date" type="date" required />
                        </FormField>
                        <FormField label="Gender" :for="`child-gender-${index}`" :error="childError(index, 'gender')">
                            <SelectInput :id="`child-gender-${index}`" v-model="child.gender" :options="genders" placeholder="Select" required />
                        </FormField>
                    </div>
                </div>

                <Button variant="secondary" size="sm" class="self-start" @click="addChild">+ Add a child</Button>
            </fieldset>

            <Button type="submit" :loading="form.processing">Create my account</Button>
            <p class="text-center text-caption text-ink-muted">
                Already have an account? <Link href="/login" class="text-brand-soft underline">Log in</Link> to connect with {{ link.trainer_name }}.
            </p>
        </form>
    </Card>
</template>
