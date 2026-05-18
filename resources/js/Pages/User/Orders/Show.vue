<template>
    <ShopLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center gap-4 mb-8">
                <Link :href="route('orders.index')" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </Link>
                <div>
                    <h1 class="text-3xl font-bold">Order {{ order.order_number }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ new Date(order.created_at).toLocaleDateString() }}</p>
                </div>
                <span class="ml-auto px-3 py-1 rounded-full text-sm font-medium capitalize" :class="statusClass(order.status)">{{ order.status }}</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-lg font-semibold mb-4">Items</h2>
                        <div class="divide-y">
                            <div v-for="item in order.items" :key="item.id" class="flex items-center gap-3 py-3">
                                <div class="w-12 h-12 bg-gray-100 rounded overflow-hidden flex-shrink-0">
                                    <LazyImage v-if="item.product?.images?.length"
                                         :src="`/${item.product.images[0].icon_path || item.product.images[0].image_path}`"
                                         :alt="item.product_name"
                                         img-class="object-contain" />
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium">{{ item.product_name }}</p>
                                    <p class="text-sm text-gray-500">Qty: {{ item.quantity }} × ${{ Number(item.price).toFixed(2) }}</p>
                                </div>
                                <p class="font-medium">${{ Number(item.subtotal).toFixed(2) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-lg font-semibold mb-4">Shipping Address</h2>
                        <p class="text-gray-600">{{ order.shipping_name }}</p>
                        <p class="text-gray-600">{{ order.shipping_address }}</p>
                        <p v-if="order.shipping_city" class="text-gray-600">{{ order.shipping_city }}, {{ order.shipping_state }} {{ order.shipping_postal_code }}</p>
                        <p class="text-gray-600">{{ order.shipping_country }}</p>
                        <p v-if="order.shipping_phone" class="text-gray-600">{{ order.shipping_phone }}</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-lg font-semibold mb-4">Order Summary</h2>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Subtotal</span>
                                <span>${{ Number(order.subtotal).toFixed(2) }}</span>
                            </div>
                            <div v-if="order.discount > 0" class="flex justify-between text-green-600">
                                <span>Discount</span>
                                <span>-${{ Number(order.discount).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Shipping</span>
                                <span>{{ Number(order.shipping) === 0 ? 'FREE' : '$' + Number(order.shipping).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Tax</span>
                                <span>${{ Number(order.tax).toFixed(2) }}</span>
                            </div>
                            <div class="border-t pt-2 flex justify-between font-bold text-lg">
                                <span>Total</span>
                                <span>${{ Number(order.total).toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-lg font-semibold mb-4">Payment</h2>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Method</span>
                                <span class="capitalize">{{ order.payment_method }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Status</span>
                                <span class="font-medium capitalize" :class="order.payment_status === 'paid' ? 'text-green-600' : 'text-yellow-600'">{{ order.payment_status }}</span>
                            </div>
                        </div>

                        <button v-if="order.payment_status === 'failed' && order.payment_method !== 'offline'"
                            @click="retryPayment"
                            class="w-full mt-4 bg-indigo-600 text-white py-2 rounded-lg text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50"
                            :disabled="retrying">
                            {{ retrying ? 'Processing...' : 'Retry Payment' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </ShopLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import ShopLayout from '../../../Layouts/ShopLayout.vue';
import LazyImage from '../../../components/LazyImage.vue';

const props = defineProps({
    order: { type: Object, required: true },
});

const retrying = ref(false);

const statusClass = (status) => {
    return {
        pending: 'bg-yellow-100 text-yellow-800',
        processing: 'bg-blue-100 text-blue-800',
        shipped: 'bg-indigo-100 text-indigo-800',
        delivered: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800',
    }[status] || 'bg-gray-100 text-gray-800';
};

async function retryPayment() {
    retrying.value = true;
    try {
        const response = await axios.post(`/api/payments/${props.order.id}/retry`);
        window.location.href = response.data.checkout_url;
    } catch (e) {
        alert(e.response?.data?.error || 'Failed to retry payment');
    } finally {
        retrying.value = false;
    }
}
</script>
