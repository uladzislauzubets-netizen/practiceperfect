<script setup lang="ts">
import Badge from '@/Components/Badge.vue';
import Button from '@/Components/Button.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import FormField from '@/Components/FormField.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextArea from '@/Components/TextArea.vue';
import TextInput from '@/Components/TextInput.vue';
import { formatDateTime } from '@/lib/format';
import type { Option, Paginated } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

interface UserRow {
    id: number;
    name: string;
    email: string;
    business_name: string | null;
    role: string;
    role_label: string;
    status: string;
    status_label: string;
    last_login_at: string | null;
    can: { update: boolean; impersonate: boolean; deactivate: boolean; reactivate: boolean; delete: boolean };
}

const props = defineProps<{
    users: Paginated<UserRow>;
    filters: { q: string; role: string; status: string };
    roles: Option[];
    statuses: Option[];
}>();

const filters = reactive({ ...props.filters });
const pending = ref<{ type: 'impersonate' | 'deactivate' | 'delete'; user: UserRow } | null>(null);
const busy = ref(false);
const deleteForm = useForm({ reason: '' });

function search() {
    router.get('/admin/users', { q: filters.q || undefined, role: filters.role || undefined, status: filters.status || undefined }, { preserveState: true, replace: true });
}

function reset() {
    filters.q = '';
    filters.role = '';
    filters.status = '';
    search();
}

const modal = computed(() => {
    if (!pending.value) {
        return null;
    }

    const { type, user } = pending.value;

    return {
        impersonate: {
            title: 'Impersonate user',
            message: `View platform as ${user.name} (${user.role_label})?`,
            confirm: 'Impersonate',
            danger: false,
        },
        deactivate: {
            title: 'Deactivate user',
            message: 'User will not be able to log in. All historical data will be preserved for analytics and compliance. Continue?',
            confirm: 'Deactivate',
            danger: true,
        },
        delete: {
            title: 'Delete user permanently',
            message: "Personal information will be removed. Historical records will show 'Deleted User'. This cannot be undone. Continue?",
            confirm: 'Delete user',
            danger: true,
        },
    }[type];
});

function close() {
    pending.value = null;
    deleteForm.reset();
    deleteForm.clearErrors();
}

function confirm() {
    if (!pending.value) {
        return;
    }

    const { type, user } = pending.value;

    if (type === 'delete') {
        deleteForm.delete(`/admin/users/${user.id}`, { onSuccess: close, preserveScroll: true });

        return;
    }

    busy.value = true;
    router.post(`/admin/users/${user.id}/${type}`, {}, { preserveScroll: true, onFinish: () => ((busy.value = false), close()) });
}

function reactivate(user: UserRow) {
    router.post(`/admin/users/${user.id}/reactivate`, {}, { preserveScroll: true });
}

const statusTone = (status: string) => (status === 'active' ? 'success' : status === 'inactive' ? 'warning' : 'danger');
</script>

<template>
    <Head title="Users" />
    <PageHeader title="Users" eyebrow="Super Admin" subtitle="Every account on the platform. Search here only looks at users.">
        <Button href="/admin/users/create">Create user</Button>
    </PageHeader>

    <form class="mb-6 grid gap-3 rounded-md border border-border-soft bg-surface p-4 sm:grid-cols-2 xl:grid-cols-[1fr_180px_180px_auto]" role="search" @submit.prevent="search">
        <FormField label="Search users" for="q">
            <TextInput id="q" v-model="filters.q" type="search" placeholder="Name or email" />
        </FormField>
        <FormField label="Role" for="role"><SelectInput id="role" v-model="filters.role" :options="roles" placeholder="All roles" /></FormField>
        <FormField label="Status" for="status"><SelectInput id="status" v-model="filters.status" :options="statuses" placeholder="All statuses" /></FormField>
        <div class="flex items-end gap-2">
            <Button type="submit">Search</Button>
            <Button variant="ghost" @click="reset">Reset</Button>
        </div>
    </form>

    <EmptyState v-if="users.data.length === 0" title="No users found" message="Try a different search or clear the filters." />

    <div v-else class="overflow-x-auto rounded-md border border-border-soft">
        <table class="w-full min-w-[760px] text-left text-body">
            <thead class="bg-surface text-caption text-ink-subtle uppercase">
                <tr>
                    <th scope="col" class="px-4 py-3">User</th>
                    <th scope="col" class="px-4 py-3">Role</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3">Last login</th>
                    <th scope="col" class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in users.data" :key="user.id" class="border-t border-border-soft" :class="user.status !== 'active' ? 'opacity-70' : ''">
                    <td class="px-4 py-3">
                        <p class="font-semibold">{{ user.name }}</p>
                        <p class="text-caption text-ink-subtle">{{ user.email }}<span v-if="user.business_name"> · {{ user.business_name }}</span></p>
                    </td>
                    <td class="px-4 py-3">{{ user.role_label }}</td>
                    <td class="px-4 py-3"><Badge :tone="statusTone(user.status)">{{ user.status_label }}</Badge></td>
                    <td class="px-4 py-3 text-ink-muted">{{ user.last_login_at ? formatDateTime(user.last_login_at) : 'Never' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap justify-end gap-2">
                            <Link v-if="user.can.update" :href="`/admin/users/${user.id}/edit`" class="rounded-sm border border-border-strong px-3 py-1.5 text-caption font-semibold hover:border-brand hover:text-brand">Edit</Link>
                            <Button v-if="user.can.impersonate" size="sm" variant="secondary" @click="pending = { type: 'impersonate', user }">Impersonate</Button>
                            <Button v-if="user.can.deactivate" size="sm" variant="ghost" @click="pending = { type: 'deactivate', user }">Deactivate</Button>
                            <Button v-if="user.can.reactivate" size="sm" variant="secondary" @click="reactivate(user)">Reactivate</Button>
                            <Button v-if="user.can.delete" size="sm" variant="danger" @click="pending = { type: 'delete', user }">Delete</Button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <Pagination :paginator="users" />

    <ConfirmModal
        v-if="modal"
        :show="pending !== null"
        :title="modal.title"
        :message="modal.message"
        :confirm-label="modal.confirm"
        :danger="modal.danger"
        :loading="busy || deleteForm.processing"
        :confirm-disabled="pending?.type === 'delete' && deleteForm.reason.trim().length < 5"
        @close="close"
        @confirm="confirm"
    >
        <FormField v-if="pending?.type === 'delete'" label="Reason for deletion (kept in the compliance log)" for="reason" :error="deleteForm.errors.reason">
            <TextArea id="reason" v-model="deleteForm.reason" :invalid="!!deleteForm.errors.reason" />
        </FormField>
    </ConfirmModal>
</template>
