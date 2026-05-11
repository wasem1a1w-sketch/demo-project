<template>
    <div class="px-4 py-6 sm:px-0">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Orders</h1>

        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="order in orders.data" :key="order.id">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-indigo-600 dark:text-indigo-400">
                            <Link :href="route('admin.orders.show', { id: order.id })">{{ order.order_number }}</Link>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ order.shipping_name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="statusClass(order.status)" class="px-2.5 py-1 rounded-full text-xs font-medium">{{ order.status }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${{ order.total }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ order.created_at }}</td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <Link :href="route('admin.orders.show', { id: order.id })" class="inline-flex items-center px-3 py-1 bg-indigo-600 dark:bg-indigo-500 border border-transparent rounded-md font-semibold text-xs text-white dark:text-indigo-100 hover:bg-indigo-500 dark:hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">View</Link>
                        </td>
                    </tr>
                    <tr v-if="orders.data.length === 0">
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No orders yet</td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ orders: Object });

function statusClass(status) {
    const base = 'px-2.5 py-1 rounded-full text-xs font-medium';
    const classes = {
        pending: `${base} bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400`,
        processing: `${base} bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400`,
        shipped: `${base} bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-400`,
        delivered: `${base} bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400`,
        cancelled: `${base} bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400`
    };
    return classes[status] || `${base} bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300`;
}
</script>