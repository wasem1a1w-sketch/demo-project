<template>
    <div class="px-4 py-6 sm:px-0">
        <div class="flex items-center gap-4 mb-6">
            <Link :href="route('admin.coupons')" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </Link>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Add Coupon</h1>
        </div>

        <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Code *</label>
                    <input v-model="form.code" type="text" required class="input uppercase">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type *</label>
                    <select v-model="form.type" required class="input">
                        <option value="percentage">Percentage</option>
                        <option value="fixed">Fixed Amount</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Value *</label>
                    <input v-model="form.value" type="number" step="0.01" min="0" required class="input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Min Order Amount</label>
                    <input v-model="form.min_order_amount" type="number" step="0.01" min="0" class="input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Discount Amount</label>
                    <input v-model="form.max_discount_amount" type="number" step="0.01" min="0" class="input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Usage Limit</label>
                    <input v-model="form.usage_limit" type="number" min="1" class="input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valid From</label>
                    <input v-model="form.valid_from" type="date" class="input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valid Until</label>
                    <input v-model="form.valid_until" type="date" class="input">
                </div>
                <div class="md:col-span-2">
                    <label class="flex items-center text-gray-700 dark:text-gray-300">
                        <input v-model="form.is_active" type="checkbox" class="mr-2 rounded border-gray-300 dark:border-gray-600">
                        Active
                    </label>
                </div>
            </div>
            <div class="mt-6 flex gap-4">
                <button type="submit" class="btn-primary">Save Coupon</button>
                <Link :href="route('admin.coupons')" class="btn-secondary">Cancel</Link>
            </div>
        </form>
    </div>
</template>

<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const form = reactive({
    code: '',
    type: 'percentage',
    value: '',
    min_order_amount: '',
    max_discount_amount: '',
    usage_limit: '',
    valid_from: '',
    valid_until: '',
    is_active: true,
});

function submit() {
    router.post(route('admin.coupons.store'), form, {
        onSuccess: () => router.visit(route('admin.coupons')),
    });
}
</script>
