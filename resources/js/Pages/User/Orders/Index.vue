<template>
    <ShopLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-3xl font-bold mb-8">My Orders</h1>

            <div v-if="orders.data.length === 0" class="text-center py-12">
                <p class="text-gray-500 mb-4">You haven't placed any orders yet.</p>
                <Link :href="route('shop')" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700">
                    Start Shopping
                </Link>
            </div>

            <div v-else class="space-y-4">
                <div v-for="order in orders.data" :key="order.id"
                    class="bg-white rounded-lg shadow-sm p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <p class="font-semibold text-lg">{{ order.order_number }}</p>
                        <p class="text-sm text-gray-500">{{ new Date(order.created_at).toLocaleDateString() }}</p>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ order.items.length }} item(s) · ${{ Number(order.total).toFixed(2) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="px-2 py-1 rounded-full text-xs font-medium capitalize" :class="statusClass(order.status)">{{ order.status }}</span>
                        <Link :href="route('orders.show', { orderNumber: order.order_number })"
                            class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                            View
                        </Link>
                    </div>
                </div>

                <div v-if="orders.links.length > 3" class="mt-6 flex justify-center gap-1">
                    <template v-for="link in orders.links" :key="link.label">
                        <span v-if="!link.url" class="px-3 py-1 text-gray-400 text-sm" v-html="link.label"></span>
                        <Link v-else :href="link.url" class="px-3 py-1 rounded text-sm"
                            :class="link.active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border'"
                            v-html="link.label"></Link>
                    </template>
                </div>
            </div>
        </div>
    </ShopLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import ShopLayout from '../../../Layouts/ShopLayout.vue';

defineProps({
    orders: {
        type: Object,
        default: () => ({ data: [], links: [] })
    }
});

const statusClass = (status) => {
    return {
        pending: 'bg-yellow-100 text-yellow-800',
        processing: 'bg-blue-100 text-blue-800',
        shipped: 'bg-indigo-100 text-indigo-800',
        delivered: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800',
    }[status] || 'bg-gray-100 text-gray-800';
};
</script>
