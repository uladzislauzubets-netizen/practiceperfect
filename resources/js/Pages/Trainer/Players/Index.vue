<script setup lang="ts">
import Badge from '@/Components/Badge.vue';
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import FormField from '@/Components/FormField.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import { DAY_NAMES, formatDate } from '@/lib/format';
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

interface Player {
    id: number;
    name: string;
    age: number | null;
    gender: string | null;
    skill_level: string | null;
    is_child: boolean;
    contact_name: string | null;
    contact_email: string | null;
    contact_phone: string | null;
    connected_at: string | null;
    best_times: string;
}

const props = defineProps<{
    players: Player[];
    filters: { day: number | null; from: string; to: string };
    best_windows: { label: string; players: number }[];
}>();

const filter = reactive({ day: props.filters.day ? String(props.filters.day) : '', from: props.filters.from, to: props.filters.to });
const removing = ref<Player | null>(null);
const skill = reactive<Record<number, string>>(Object.fromEntries(props.players.map((player) => [player.id, player.skill_level ?? ''])));

const dayOptions = DAY_NAMES.map((name, index) => ({ value: String(index + 1), label: name }));

function applyFilter() {
    router.get(
        '/trainer/players',
        { day: filter.day || undefined, from: filter.day ? filter.from || undefined : undefined, to: filter.day ? filter.to || undefined : undefined },
        { preserveState: true, replace: true },
    );
}

function clearFilter() {
    filter.day = '';
    filter.from = '';
    filter.to = '';
    applyFilter();
}

function saveSkill(player: Player) {
    router.patch(`/trainer/players/${player.id}`, { skill_level: skill[player.id] || null }, { preserveScroll: true });
}

function remove() {
    if (removing.value) {
        router.delete(`/trainer/players/${removing.value.id}`, { preserveScroll: true, onFinish: () => (removing.value = null) });
    }
}
</script>

<template>
    <Head title="Players" />
    <PageHeader title="Players" eyebrow="Trainer" subtitle="Everyone connected to your organization." />

    <div class="mb-6 grid gap-6 lg:grid-cols-[2fr_1fr]">
        <Card title="Who is available?" eyebrow="Filter by Best Times">
            <form class="grid gap-3 sm:grid-cols-[1fr_1fr_1fr_auto]" @submit.prevent="applyFilter">
                <FormField label="Day" for="day"><SelectInput id="day" v-model="filter.day" :options="dayOptions" placeholder="Any day" /></FormField>
                <FormField label="From" for="from"><TextInput id="from" v-model="filter.from" type="time" :disabled="!filter.day" :required="!!filter.day" /></FormField>
                <FormField label="To (optional)" for="to"><TextInput id="to" v-model="filter.to" type="time" :disabled="!filter.day" /></FormField>
                <div class="flex items-end gap-2">
                    <Button type="submit">Filter</Button>
                    <Button v-if="filters.day" variant="ghost" @click="clearFilter">Clear</Button>
                </div>
            </form>
        </Card>

        <Card title="Most popular times" eyebrow="Suggested for sessions">
            <ul v-if="best_windows.length" class="flex flex-col gap-2">
                <li v-for="window in best_windows" :key="window.label" class="flex items-center justify-between gap-3 text-body">
                    <span class="font-semibold">{{ window.label }}</span>
                    <span class="text-caption text-ink-muted">{{ window.players }} available</span>
                </li>
            </ul>
            <p v-else class="text-body text-ink-muted">No availability shared yet.</p>
        </Card>
    </div>

    <EmptyState
        v-if="players.length === 0"
        :title="filters.day ? 'Nobody is available then' : 'No players yet'"
        :message="filters.day ? 'Try a different day or time.' : 'Share your ShareLink so players and parents can join.'"
    >
        <Button v-if="!filters.day" href="/trainer/share-link">Get my ShareLink</Button>
    </EmptyState>

    <div v-else class="grid gap-4">
        <article v-for="player in players" :key="player.id" class="rounded-md border border-border-soft bg-surface p-5 shadow-card-soft">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-card font-semibold">{{ player.name }}
                        <Badge v-if="player.is_child" tone="info">Child</Badge>
                    </h2>
                    <p class="text-caption text-ink-subtle">
                        <span v-if="player.age !== null">Age {{ player.age }} · </span>
                        <span v-if="player.gender" class="capitalize">{{ player.gender }} · </span>
                        Connected {{ formatDate(player.connected_at) }}
                    </p>
                    <p class="mt-2 text-body text-ink-muted">
                        Contact: {{ player.contact_name }} · {{ player.contact_email }}<span v-if="player.contact_phone"> · {{ player.contact_phone }}</span>
                    </p>
                    <p class="mt-1 text-body">Best Times: <span class="text-ink-muted">{{ player.best_times || 'Not shared yet' }}</span></p>
                </div>
                <div class="flex flex-col items-stretch gap-2 sm:items-end">
                    <form class="flex items-end gap-2" @submit.prevent="saveSkill(player)">
                        <FormField label="Skill level" :for="`skill-${player.id}`">
                            <TextInput :id="`skill-${player.id}`" v-model="skill[player.id]" class="w-40" maxlength="50" />
                        </FormField>
                        <Button type="submit" size="sm" variant="secondary">Save</Button>
                    </form>
                    <Button size="sm" variant="ghost" @click="removing = player">Remove from roster</Button>
                </div>
            </div>
        </article>
    </div>

    <ConfirmModal
        v-if="removing"
        :show="true"
        title="Remove from roster"
        :message="`Remove ${removing.name} from your organization? Their history is kept.`"
        confirm-label="Remove"
        danger
        @close="removing = null"
        @confirm="remove"
    />
</template>
