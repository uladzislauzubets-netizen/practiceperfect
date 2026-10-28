<script setup lang="ts">
import Button from '@/Components/Button.vue';
import Modal from '@/Components/Modal.vue';

withDefaults(
    defineProps<{
        show: boolean;
        title: string;
        message: string;
        confirmLabel: string;
        danger?: boolean;
        loading?: boolean;
        confirmDisabled?: boolean;
    }>(),
    { danger: false, loading: false, confirmDisabled: false },
);

const emit = defineEmits<{ close: []; confirm: [] }>();
</script>

<template>
    <Modal :show="show" :title="title" size="sm" @close="emit('close')">
        <p class="text-body text-ink-muted">{{ message }}</p>
        <div v-if="$slots.default" class="mt-4"><slot /></div>
        <template #footer>
            <Button variant="ghost" @click="emit('close')">Cancel</Button>
            <Button :variant="danger ? 'danger' : 'primary'" :loading="loading" :disabled="confirmDisabled" @click="emit('confirm')">
                {{ confirmLabel }}
            </Button>
        </template>
    </Modal>
</template>
