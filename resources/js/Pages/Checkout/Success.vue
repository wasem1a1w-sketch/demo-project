<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold mb-4">Order Placed Successfully!</h1>
            <p class="text-gray-600 mb-2">Thank you for your order.</p>
            <p class="text-lg mb-8">Order Number: <span class="font-semibold">{{ order.order_number }}</span></p>
            
            <div class="bg-white rounded-lg shadow-sm p-6 max-w-md mx-auto text-left mb-8">
                <h2 class="font-semibold mb-4">Order Details</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="capitalize">{{ order.status }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Items:</span>
                        <span>{{ order.items.length }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total:</span>
                        <span class="font-semibold">${{ order.total }}</span>
                    </div>
                </div>
            </div>
            
            <Link :href="route('shop')" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700">
                Continue Shopping
            </Link>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    order: {
        type: Object,
        required: true,
    },
});
</script>