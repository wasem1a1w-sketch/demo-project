<template>
    <div class="relative" @mouseenter="open = true" @mouseleave="open = false">
        <button class="flex items-center gap-1 text-sm font-medium transition-colors" :class="active ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white'">
            <slot name="trigger" />
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </button>
        <div v-show="open" class="absolute left-0 pt-2 w-48 z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-100 dark:border-gray-700 py-1">
                <slot name="content" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
const open = ref(false);
defineProps({
    active: Boolean
});
</script>
