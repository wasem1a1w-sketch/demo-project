<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" class="fixed inset-0 z-[70] flex items-center justify-center p-4" @click.self="cancel">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="cancel"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ title }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">{{ message }}</p>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="cancel"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            {{ cancelText }}
                        </button>
                        <button type="button" @click="confirm"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition"
                            :class="confirmClass">
                            {{ confirmText }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { watch } from 'vue';

const props = defineProps({
    show: Boolean,
    title: { type: String, default: 'Confirm' },
    message: { type: String, default: 'Are you sure?' },
    confirmText: { type: String, default: 'Delete' },
    cancelText: { type: String, default: 'Cancel' },
    confirmClass: { type: String, default: 'bg-red-600 hover:bg-red-700' },
});

const emit = defineEmits(['confirm', 'cancel', 'update:show']);

function confirm() {
    emit('confirm');
    emit('update:show', false);
}

function cancel() {
    emit('cancel');
    emit('update:show', false);
}

watch(() => props.show, (val) => {
    if (val) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-active > div:last-child,
.modal-leave-active > div:last-child {
    transition: transform 0.25s ease, opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-from > div:last-child,
.modal-leave-to > div:last-child {
    transform: scale(0.95);
    opacity: 0;
}
</style>
