<template>
    <div v-if="meta.last_page > 1" class="flex items-center justify-between px-4 py-3 sm:px-6">
        <div class="hidden sm:block text-sm text-gray-500 dark:text-gray-400">
            Showing <span class="font-medium">{{ meta.from }}</span> to <span class="font-medium">{{ meta.to }}</span> of <span class="font-medium">{{ meta.total }}</span> results
        </div>
        <div class="flex items-center gap-1">
            <Link v-if="meta.prev_page_url" :href="meta.prev_page_url" class="px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Previous
            </Link>
            <template v-for="(link, idx) in meta.links" :key="idx">
                <Link v-if="link.url && !isNaN(link.label)" :href="link.url"
                    class="px-3 py-1.5 text-sm font-medium rounded-lg transition"
                    :class="link.active
                        ? 'bg-indigo-600 text-white'
                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'">
                    {{ link.label }}
                </Link>
                <span v-else-if="!link.url && !isNaN(link.label)"
                    class="px-3 py-1.5 text-sm font-medium text-gray-400 dark:text-gray-500">
                    {{ link.label }}
                </span>
            </template>
            <Link v-if="meta.next_page_url" :href="meta.next_page_url" class="px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Next
            </Link>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    meta: Object,
});
</script>
