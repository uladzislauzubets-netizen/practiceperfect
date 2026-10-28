<script setup lang="ts">
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import Checkbox from '@/Components/Checkbox.vue';
import FormField from '@/Components/FormField.vue';
import PageHeader from '@/Components/PageHeader.vue';
import TextInput from '@/Components/TextInput.vue';
import { applyBrandColor, darkenColor, isHexColor, lightenColor } from '@/lib/branding';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps<{ saved: { color: string | null; logo_url: string | null }; default_color: string }>();

const form = useForm({
    brand_color: props.saved.color ?? props.default_color,
    logo: null as File | null,
    remove_logo: false,
});

const previewLogo = ref<string | null>(null);
const color = computed(() => (isHexColor(form.brand_color) ? form.brand_color : props.default_color));
const shownLogo = computed(() => (form.remove_logo ? null : (previewLogo.value ?? props.saved.logo_url)));

watch(color, (value) => applyBrandColor(value), { immediate: true });
onBeforeUnmount(() => previewLogo.value && URL.revokeObjectURL(previewLogo.value));

function pickLogo(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    if (previewLogo.value) {
        URL.revokeObjectURL(previewLogo.value);
    }

    form.logo = file;
    form.remove_logo = false;
    previewLogo.value = file ? URL.createObjectURL(file) : null;
}

function save() {
    form.post('/trainer/branding', { forceFormData: true, preserveScroll: true, onSuccess: () => ((form.logo = null), (previewLogo.value = null)) });
}

function reset() {
    router.post('/trainer/branding/reset', {}, { preserveScroll: true, onSuccess: () => (form.brand_color = props.default_color) });
}
</script>

<template>
    <Head title="Branding" />
    <PageHeader title="Portal branding" eyebrow="Trainer" subtitle="Your logo and color are shown to your players, parents and coaches." />

    <div class="grid gap-6 lg:grid-cols-2">
        <Card title="Settings">
            <form class="flex flex-col gap-5" @submit.prevent="save">
                <FormField label="Logo" for="logo" hint="PNG, JPG or SVG up to 2MB. Larger images are resized to fit 200 by 200." :error="form.errors.logo">
                    <input id="logo" type="file" accept="image/png,image/jpeg,image/svg+xml" class="text-caption text-ink-muted file:mr-3 file:rounded-xs file:border-0 file:bg-surface-raised file:px-3 file:py-1.5 file:text-ink" @change="pickLogo" />
                </FormField>
                <Checkbox v-if="saved.logo_url && !form.logo" v-model="form.remove_logo" label="Remove current logo" />

                <FormField label="Primary color" for="brand_color" hint="Used for buttons, gradients and accents." :error="form.errors.brand_color">
                    <div class="flex items-center gap-3">
                        <input
                            v-model="form.brand_color"
                            type="color"
                            aria-label="Pick a color"
                            class="h-10 w-14 cursor-pointer rounded-sm border border-border-soft bg-page p-1"
                        />
                        <TextInput id="brand_color" v-model="form.brand_color" class="max-w-40" maxlength="7" placeholder="#00B300" :invalid="!!form.errors.brand_color" />
                    </div>
                </FormField>

                <div class="flex flex-wrap gap-3">
                    <Button type="submit" :loading="form.processing">Save branding</Button>
                    <Button variant="ghost" @click="reset">Reset to default color</Button>
                </div>
            </form>
        </Card>

        <Card title="Preview" eyebrow="Live">
            <div class="flex flex-col gap-5 rounded-md border border-border-soft bg-page p-5">
                <div class="flex items-center gap-3">
                    <img v-if="shownLogo" :src="shownLogo" alt="Logo preview" class="size-12 rounded-sm object-contain" />
                    <span v-else class="inline-flex size-12 items-center justify-center rounded-sm bg-gradient-to-b from-brand to-brand-deep text-section font-bold text-white">P</span>
                    <span class="text-block font-bold">Your business</span>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Button>Primary action</Button>
                    <Button variant="secondary">Secondary</Button>
                </div>
                <div class="flex gap-2" aria-hidden="true">
                    <span class="h-8 flex-1 rounded-xs" :style="{ background: lightenColor(color, 20) }" />
                    <span class="h-8 flex-1 rounded-xs" :style="{ background: color }" />
                    <span class="h-8 flex-1 rounded-xs" :style="{ background: darkenColor(color, 20) }" />
                </div>
            </div>
        </Card>
    </div>
</template>
