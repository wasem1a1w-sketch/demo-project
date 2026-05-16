<template>
    <div class="px-4 py-6 sm:px-0">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Shipping & Tax Settings</h1>

        <form @submit.prevent="save" class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-6 max-w-lg">
            <div class="space-y-6">
                <div>
                    <label for="shipping_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shipping Rate ($)</label>
                    <input id="shipping_rate" v-model="form.shipping_rate" type="number" step="0.01" min="0"
                        class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p v-if="errors.shipping_rate" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.shipping_rate }}</p>
                </div>

                <div>
                    <label for="free_shipping_threshold" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Free Shipping Threshold ($)</label>
                    <input id="free_shipping_threshold" v-model="form.free_shipping_threshold" type="number" step="0.01" min="0"
                        class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Orders at or above this amount get free shipping.</p>
                    <p v-if="errors.free_shipping_threshold" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.free_shipping_threshold }}</p>
                </div>

                <div>
                    <label for="tax_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tax Rate (%)</label>
                    <input id="tax_rate" v-model="form.tax_rate" type="number" step="0.1" min="0" max="100"
                        class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p v-if="errors.tax_rate" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.tax_rate }}</p>
                </div>
            </div>

            <div class="mt-6 flex gap-4">
                <button type="submit"
                    class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { useNotification } from '../../../composables/useNotification';

const { success } = useNotification();

const props = defineProps({ settings: Object });

const form = reactive({
    shipping_rate: props.settings.shipping_rate ?? 15,
    free_shipping_threshold: props.settings.free_shipping_threshold ?? 100,
    tax_rate: props.settings.tax_rate ?? 10,
});

const errors = ref({});

function save() {
    errors.value = {};
    router.put(route('admin.settings.update'), form, {
        preserveScroll: true,
        onSuccess: () => {
            success('Settings updated successfully!');
        },
        onError: (errs) => { errors.value = errs; },
    });
}
</script>
