<template>
    <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Order {{ order.order_number }}</h1>
            <Link :href="route('admin.orders')" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">Back to Orders</Link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Order Details</h3>
                <form @submit.prevent="updateOrder">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select v-model="form.status" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Status</label>
                        <select v-model="form.payment_status" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Update</button>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Shipping Address</h3>
                <p class="text-gray-900 dark:text-white">{{ order.shipping_name }}</p>
                <p class="text-gray-500 dark:text-gray-400">{{ order.shipping_address }}</p>
                <p class="text-gray-500 dark:text-gray-400">{{ order.shipping_city }}, {{ order.shipping_state }} {{ order.shipping_postal_code }}</p>
                <p class="text-gray-500 dark:text-gray-400">{{ order.shipping_country }}</p>
                <p class="text-gray-500 dark:text-gray-400">{{ order.shipping_phone }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg mt-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Order Items</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Qty</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="item in order.items" :key="item.id">
                        <td class="px-6 py-4 text-gray-900 dark:text-white">{{ item.product_name }}</td>
                        <td class="px-6 py-4 text-gray-900 dark:text-white">${{ item.price }}</td>
                        <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ item.quantity }}</td>
                        <td class="px-6 py-4 text-gray-900 dark:text-white">${{ item.subtotal }}</td>
                    </tr>
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <td colspan="3" class="px-6 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Subtotal</td>
                        <td class="px-6 py-3 text-gray-900 dark:text-white">${{ order.subtotal }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-6 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Tax</td>
                        <td class="px-6 py-3 text-gray-900 dark:text-white">${{ order.tax }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-6 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Shipping</td>
                        <td class="px-6 py-3 text-gray-900 dark:text-white">${{ order.shipping }}</td>
                    </tr>
                    <tr v-if="order.discount > 0">
                        <td colspan="3" class="px-6 py-3 text-right font-medium text-green-600 dark:text-green-400">Discount</td>
                        <td class="px-6 py-3 text-green-600 dark:text-green-400">-${{ order.discount }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-6 py-3 text-right font-bold text-gray-900 dark:text-white">Total</td>
                        <td class="px-6 py-3 font-bold text-gray-900 dark:text-white">${{ order.total }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({ order: Object });

const form = reactive({
    status: props.order.status,
    payment_status: props.order.payment_status,
});

function updateOrder() {
    router.put(route('admin.orders.update', { id: props.order.id }), form, {
        onSuccess: () => {
            alert('Order updated');
        },
    });
}
</script>