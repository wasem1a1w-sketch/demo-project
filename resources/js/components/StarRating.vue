<template>
    <div class="flex items-center gap-0.5">
        <button
            v-for="i in 5"
            :key="i"
            type="button"
            :disabled="readonly"
            :class="[
                'transition-colors',
                small ? 'text-sm' : 'text-2xl',
                i <= modelValue ? 'text-yellow-400' : 'text-gray-300',
                readonly ? 'cursor-default' : 'cursor-pointer hover:scale-110',
            ]"
            @click="setRating(i)"
        >
            ★
        </button>
        <span v-if="showValue" class="ml-2 text-sm text-gray-500 dark:text-gray-400">{{ modelValue }}/5</span>
    </div>
</template>

<script setup>
const props = defineProps({
    modelValue: { type: Number, default: 0 },
    readonly: { type: Boolean, default: false },
    showValue: { type: Boolean, default: false },
    small: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

function setRating(value) {
    if (!props.readonly) {
        emit('update:modelValue', value)
    }
}
</script>
