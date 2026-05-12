<template>
    <div class="px-4 py-6 sm:px-0">
        <div class="mb-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-4">
                    <Link :href="route('admin.orders')" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Order {{ order.order_number }}</h1>
                            <span :class="statusBadgeClass(order.status)" class="px-3 py-1 rounded-full text-sm font-medium">{{ order.status }}</span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Placed on {{ formatDate(order.created_at) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="lg:col-span-2">
                <div v-if="can('orders.update')" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Update Order Status</h3>
                    </div>
                    <form @submit.prevent="updateOrder" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status</label>
                                <div class="relative">
                                    <select v-model="form.status" class="block w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow appearance-none">
                                        <option value="pending">Pending</option>
                                        <option value="processing">Processing</option>
                                        <option value="shipped">Shipped</option>
                                        <option value="delivered">Delivered</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Payment Status</label>
                                <div class="relative">
                                    <select v-model="form.payment_status" class="block w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow appearance-none">
                                        <option value="pending">Pending</option>
                                        <option value="paid">Paid</option>
                                        <option value="failed">Failed</option>
                                        <option value="refunded">Refunded</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end pt-1">
                            <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 text-white px-5 py-2.5 rounded-lg hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors font-medium text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Update Order
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mt-6 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                        <div class="p-1.5 bg-gray-100 dark:bg-gray-700 rounded-lg">
                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4l-8-4m-8 4l8 4"></path></svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Order Items</h3>
                        <span class="ml-auto text-sm text-gray-500 dark:text-gray-400">{{ order.items.length }} item{{ order.items.length !== 1 ? 's' : '' }}</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product</th>
                                    <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Price</th>
                                    <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Qty</th>
                                    <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="item in order.items" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ item.product_name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 text-right">${{ Number(item.price).toFixed(2) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white text-center">
                                        <span class="inline-flex items-center justify-center w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm font-medium">{{ item.quantity }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white text-right">${{ Number(item.subtotal).toFixed(2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 px-6 py-4">
                        <div class="max-w-xs ml-auto space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                                <span class="font-medium text-gray-900 dark:text-white">${{ Number(order.subtotal).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Tax</span>
                                <span class="font-medium text-gray-900 dark:text-white">${{ Number(order.tax).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Shipping</span>
                                <span class="font-medium text-gray-900 dark:text-white">${{ Number(order.shipping).toFixed(2) }}</span>
                            </div>
                            <div v-if="order.discount > 0" class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Discount</span>
                                <span class="font-medium text-green-600 dark:text-green-400">-${{ Number(order.discount).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-base font-bold border-t border-gray-200 dark:border-gray-600 pt-2">
                                <span class="text-gray-900 dark:text-white">Total</span>
                                <span class="text-gray-900 dark:text-white">${{ Number(order.total).toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Shipping Address</h3>
                    </div>
                    <div class="space-y-2 text-sm">
                        <p class="font-medium text-gray-900 dark:text-white text-base">{{ order.shipping_name }}</p>
                        <p class="text-gray-600 dark:text-gray-400">{{ order.shipping_address }}</p>
                        <p class="text-gray-600 dark:text-gray-400">{{ order.shipping_city }}, {{ order.shipping_state }} {{ order.shipping_postal_code }}</p>
                        <p class="text-gray-600 dark:text-gray-400">{{ order.shipping_country }}</p>
                        <div class="pt-2 flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            <span>{{ order.shipping_phone }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Order Summary</h3>
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Payment Status</dt>
                            <dd>
                                <span :class="paymentBadgeClass(order.payment_status)" class="px-2.5 py-0.5 rounded-full text-xs font-medium">{{ order.payment_status }}</span>
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Items</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ order.items.length }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Subtotal</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">${{ Number(order.subtotal).toFixed(2) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Total</dt>
                            <dd class="font-bold text-gray-900 dark:text-white">${{ Number(order.total).toFixed(2) }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Timeline</h3>
                    </div>
                    <div class="space-y-4">
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="w-2.5 h-2.5 rounded-full bg-indigo-500 ring-2 ring-indigo-100 dark:ring-indigo-900"></div>
                                <div class="w-px h-full bg-gray-200 dark:bg-gray-700"></div>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Order placed</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(order.created_at) }}</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div :class="['w-2.5 h-2.5 rounded-full', status >= 1 ? 'bg-indigo-500 ring-2 ring-indigo-100 dark:ring-indigo-900' : 'bg-gray-300 dark:bg-gray-600']"></div>
                                <div class="w-px h-full bg-gray-200 dark:bg-gray-700"></div>
                            </div>
                            <div>
                                <p class="text-sm font-medium" :class="status >= 1 ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500'">Processing</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div :class="['w-2.5 h-2.5 rounded-full', status >= 2 ? 'bg-indigo-500 ring-2 ring-indigo-100 dark:ring-indigo-900' : 'bg-gray-300 dark:bg-gray-600']"></div>
                                <div class="w-px h-full bg-gray-200 dark:bg-gray-700"></div>
                            </div>
                            <div>
                                <p class="text-sm font-medium" :class="status >= 2 ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500'">Shipped</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div :class="['w-2.5 h-2.5 rounded-full', status >= 3 ? 'bg-indigo-500 ring-2 ring-indigo-100 dark:ring-indigo-900' : 'bg-gray-300 dark:bg-gray-600']"></div>
                            </div>
                            <div>
                                <p class="text-sm font-medium" :class="status >= 3 ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500'">Delivered</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useNotification } from '../../../composables/useNotification';
import { usePermission } from '../../../composables/usePermission';

const { can } = usePermission();
const props = defineProps({ order: Object });

const { success } = useNotification();

const statusMap = { pending: 0, processing: 1, shipped: 2, delivered: 3, cancelled: -1 };
const status = computed(() => statusMap[props.order.status] ?? -1);

const form = reactive({
    status: props.order.status,
    payment_status: props.order.payment_status,
});

function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function statusBadgeClass(status) {
    const classes = {
        pending: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-700',
        processing: 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 border border-blue-200 dark:border-blue-700',
        shipped: 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-700',
        delivered: 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 border border-green-200 dark:border-green-700',
        cancelled: 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 border border-red-200 dark:border-red-700'
    };
    return classes[status] || 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-600';
}

function paymentBadgeClass(status) {
    const classes = {
        pending: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400',
        paid: 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400',
        failed: 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400',
        refunded: 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400'
    };
    return classes[status] || 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300';
}

function updateOrder() {
    router.put(route('admin.orders.update', { id: props.order.id }), form, {
        onSuccess: () => {
            success('Order updated');
        },
    });
}
</script>
