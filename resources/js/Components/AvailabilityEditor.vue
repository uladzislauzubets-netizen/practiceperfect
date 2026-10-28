<script setup lang="ts">
import { DAY_NAMES } from '@/lib/format';
import type { Slot } from '@/types';
import { reactive, watch } from 'vue';

type Mode = 'unset' | 'available' | 'unavailable';

interface Range {
    start: string;
    end: string;
}

interface DayState {
    day: number;
    mode: Mode;
    ranges: Range[];
}

const props = defineProps<{ modelValue: Slot[]; errors?: string }>();
const emit = defineEmits<{ 'update:modelValue': [slots: Slot[]] }>();

function build(slots: Slot[]): DayState[] {
    return DAY_NAMES.map((_, index) => {
        const day = index + 1;
        const daySlots = slots.filter((slot) => slot.day_of_week === day);

        if (daySlots.some((slot) => !slot.is_available)) {
            return { day, mode: 'unavailable', ranges: [] };
        }

        if (daySlots.length > 0) {
            return { day, mode: 'available', ranges: daySlots.map((slot) => ({ start: slot.start_time, end: slot.end_time })) };
        }

        return { day, mode: 'unset', ranges: [] };
    });
}

const days = reactive<DayState[]>(build(props.modelValue));

function toSlots(): Slot[] {
    return days.flatMap((state): Slot[] => {
        if (state.mode === 'available') {
            return state.ranges.map((range) => ({ day_of_week: state.day, start_time: range.start, end_time: range.end, is_available: true }));
        }

        return state.mode === 'unavailable' ? [{ day_of_week: state.day, start_time: '00:00', end_time: '23:59', is_available: false }] : [];
    });
}

watch(days, () => emit('update:modelValue', toSlots()), { deep: true });

function setMode(state: DayState, mode: Mode) {
    state.mode = mode;

    if (mode === 'available' && state.ranges.length === 0) {
        state.ranges.push({ start: '17:00', end: '20:00' });
    }
}

function addRange(state: DayState) {
    const last = state.ranges[state.ranges.length - 1];
    const start = last ? last.end : '09:00';
    const hour = Math.min(23, parseInt(start.slice(0, 2), 10) + 1);

    state.ranges.push({ start, end: `${String(hour).padStart(2, '0')}:${start.slice(3, 5)}` });
}

const modes: { value: Mode; label: string }[] = [
    { value: 'unset', label: 'Not set' },
    { value: 'available', label: 'Available' },
    { value: 'unavailable', label: 'Not available' },
];
</script>

<template>
    <div class="flex flex-col gap-3">
        <div v-for="state in days" :key="state.day" class="rounded-sm border border-border-soft bg-page p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="w-28 text-card font-semibold">{{ DAY_NAMES[state.day - 1] }}</p>
                <div class="flex rounded-sm border border-border-soft p-0.5" role="group" :aria-label="`${DAY_NAMES[state.day - 1]} availability`">
                    <button
                        v-for="mode in modes"
                        :key="mode.value"
                        type="button"
                        class="rounded-xs px-3 py-1.5 text-caption font-semibold transition"
                        :class="state.mode === mode.value ? 'bg-brand text-white' : 'text-ink-muted hover:text-ink'"
                        :aria-pressed="state.mode === mode.value"
                        @click="setMode(state, mode.value)"
                    >
                        {{ mode.label }}
                    </button>
                </div>
            </div>

            <div v-if="state.mode === 'available'" class="mt-3 flex flex-col gap-2">
                <div v-for="(range, index) in state.ranges" :key="index" class="flex flex-wrap items-center gap-2">
                    <input
                        v-model="range.start"
                        type="time"
                        step="900"
                        required
                        :aria-label="`${DAY_NAMES[state.day - 1]} start time ${index + 1}`"
                        class="h-10 rounded-sm border border-border-soft bg-surface px-3 text-body text-ink focus:border-brand focus:outline-none"
                    />
                    <span class="text-ink-subtle">to</span>
                    <input
                        v-model="range.end"
                        type="time"
                        step="900"
                        required
                        :aria-label="`${DAY_NAMES[state.day - 1]} end time ${index + 1}`"
                        class="h-10 rounded-sm border border-border-soft bg-surface px-3 text-body text-ink focus:border-brand focus:outline-none"
                    />
                    <button
                        v-if="state.ranges.length > 1"
                        type="button"
                        class="rounded-xs px-2 py-1 text-caption text-ink-subtle hover:text-danger"
                        @click="state.ranges.splice(index, 1)"
                    >
                        Remove
                    </button>
                </div>
                <button type="button" class="self-start text-caption font-semibold text-brand-soft hover:underline" @click="addRange(state)">+ Add another time range</button>
            </div>
        </div>
        <p v-if="errors" class="text-caption text-danger" role="alert">{{ errors }}</p>
    </div>
</template>
