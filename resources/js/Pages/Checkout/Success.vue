<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col">
        <header class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <Link :href="route('home')" class="flex items-center">
                        <span class="text-xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400 bg-clip-text text-transparent">Shop</span>
                    </Link>
                    <Link :href="route('shop')" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Continue Shopping</Link>
                </div>
            </div>
        </header>

        <main class="flex-1 flex items-center justify-center px-4 py-12">
            <div v-if="loading" class="text-center">
                <div class="w-16 h-16 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
                <p class="text-gray-500">Verifying payment...</p>
            </div>

            <div v-else-if="error" class="w-full max-w-2xl">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 sm:p-12 text-center">
                    <div class="w-20 h-20 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Payment Error</h1>
                    <p class="text-gray-500 dark:text-gray-400 mb-8">{{ error }}</p>
                    <Link :href="route('checkout')" class="inline-flex items-center justify-center gap-2 bg-indigo-600 text-white px-6 py-3 rounded-xl hover:bg-indigo-700 transition-colors font-medium">
                        Return to Checkout
                    </Link>
                </div>
            </div>

            <div v-else class="w-full max-w-2xl">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 sm:p-12 text-center">
                    <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>

                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Order Placed!</h1>
                    <p class="text-gray-500 dark:text-gray-400 mb-2">Thank you for your purchase.</p>
                    <p v-if="orderData" class="text-lg text-gray-700 dark:text-gray-300 mb-8">
                        Order
                        <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ orderData.order_number }}</span>
                    </p>
                    <p v-else class="text-gray-500 dark:text-gray-400 mb-8">Your order has been placed successfully.</p>

                    <div v-if="orderData" class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-6 text-left mb-8 space-y-4">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <h2 class="font-semibold text-gray-900 dark:text-white">Order Summary</h2>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-600 pt-4 space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Status</span>
                                <span class="font-medium text-gray-900 dark:text-white capitalize">{{ orderData.status }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Items</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ orderData.items?.length || 0 }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Payment</span>
                                <span class="font-medium text-gray-900 dark:text-white capitalize">{{ orderData.payment_status }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                                <span class="font-medium text-gray-900 dark:text-white">${{ Number(orderData.subtotal).toFixed(2) }}</span>
                            </div>
                            <div v-if="orderData.discount > 0" class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Discount</span>
                                <span class="font-medium text-green-600 dark:text-green-400">-${{ Number(orderData.discount).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Shipping</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ Number(orderData.shipping) === 0 ? 'FREE' : '$' + Number(orderData.shipping).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Tax</span>
                                <span class="font-medium text-gray-900 dark:text-white">${{ Number(orderData.tax).toFixed(2) }}</span>
                            </div>
                            <div class="border-t border-gray-200 dark:border-gray-600 pt-3 flex justify-between text-base">
                                <span class="font-bold text-gray-900 dark:text-white">Total</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">${{ Number(orderData.total).toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <Link :href="route('shop')" class="inline-flex items-center justify-center gap-2 bg-indigo-600 text-white px-6 py-3 rounded-xl hover:bg-indigo-700 transition-colors font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                            Continue Shopping
                        </Link>
                        <Link :href="route('orders.index')" class="inline-flex items-center justify-center gap-2 text-gray-700 dark:text-gray-300 px-6 py-3 rounded-xl border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                            View My Orders
                        </Link>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    initialOrder: {
        type: Object,
        default: null,
    },
});

const loading = ref(true);
const error = ref(null);
const orderData = ref(props.initialOrder);

onMounted(async () => {
    const params = new URLSearchParams(window.location.search);
    const sessionId = params.get('session_id');

    if (!sessionId) {
        loading.value = false;
        return;
    }

    try {
        const response = await axios.get('/api/payments/success', {
            params: { session_id: sessionId },
        });
        orderData.value = response.data.order;
    } catch (e) {
        error.value = e.response?.data?.error || 'Failed to verify payment. Please contact support.';
    } finally {
        loading.value = false;
    }
});
</script>
