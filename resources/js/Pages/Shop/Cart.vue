<template>
    <ShopLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">Shopping Cart</h1>
        
            <div v-if="cartStore.items.length === 0" class="text-center py-12">
                <svg class="w-24 h-24 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <p class="text-gray-500 dark:text-gray-400 mb-4">Your cart is empty</p>
                <Link :href="route('shop')" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700">Continue Shopping</Link>
            </div>
        
            <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-300">Product</th>
                                    <th class="px-6 py-3 text-center text-sm font-medium text-gray-500 dark:text-gray-300">Price</th>
                                    <th class="px-6 py-3 text-center text-sm font-medium text-gray-500 dark:text-gray-300">Quantity</th>
                                    <th class="px-6 py-3 text-right text-sm font-medium text-gray-500 dark:text-gray-300">Subtotal</th>
                                    <th class="px-6 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="item in cartStore.items" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden flex-shrink-0">
                                                <img v-if="item.product?.images?.length" :src="`/${item.product.images[0].image_path}`" :alt="item.product.name" class="w-full h-full object-contain">
                                                <div v-else class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <Link :href="route('product', item.product?.slug)" class="font-medium text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400">{{ item.product?.name }}</Link>
                                                <p v-if="item.product?.sku" class="text-sm text-gray-500 dark:text-gray-400">SKU: {{ item.product.sku }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center text-gray-900 dark:text-white">${{ Number(item.product?.price || 0).toFixed(2) }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center">
                                            <button @click="updateQuantity(item.id, item.quantity - 1)" class="w-8 h-8 rounded border border-gray-300 dark:border-gray-600 flex items-center justify-center hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300">-</button>
                                            <span class="w-10 text-center text-gray-900 dark:text-white">{{ item.quantity }}</span>
                                            <button @click="updateQuantity(item.id, item.quantity + 1)" class="w-8 h-8 rounded border border-gray-300 dark:border-gray-600 flex items-center justify-center hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300">+</button>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium text-gray-900 dark:text-white">${{ (Number(item.product?.price || 0) * item.quantity).toFixed(2) }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <button @click="removeItem(item.id)" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Order Summary</h2>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300">Subtotal</span>
                                <span class="text-gray-900 dark:text-white">${{ cartStore.subtotal.toFixed(2) }}</span>
                            </div>
                            <div v-if="cartStore.coupon" class="flex justify-between text-green-600 dark:text-green-400">
                                <span>Discount</span>
                                <span>-${{ cartStore.discount.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300">Tax (10%)</span>
                                <span class="text-gray-900 dark:text-white">${{ cartStore.tax.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300">Shipping</span>
                                <span class="text-gray-900 dark:text-white">{{ cartStore.shipping === 0 ? 'FREE' : '$' + cartStore.shipping.toFixed(2) }}</span>
                            </div>
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-3 flex justify-between font-bold text-lg">
                                <span class="text-gray-900 dark:text-white">Total</span>
                                <span class="text-indigo-600 dark:text-indigo-400">${{ cartStore.total.toFixed(2) }}</span>
                            </div>
                        </div>
                        <div v-if="!cartStore.coupon" class="mt-4">
                            <div class="flex">
                                <input v-model="couponCode" type="text" placeholder="Coupon code" class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-l-lg focus:ring-2 focus:ring-indigo-500">
                                <button @click="applyCoupon" :disabled="applyingCoupon" class="bg-indigo-600 text-white px-4 py-2 rounded-r-lg hover:bg-indigo-700 disabled:opacity-50">{{ applyingCoupon ? 'Applying...' : 'Apply' }}</button>
                            </div>
                            <p v-if="couponError" class="text-red-500 text-sm mt-2">{{ couponError }}</p>
                        </div>
                        <div v-else class="mt-4 flex items-center justify-between bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
                            <div>
                                <span class="text-green-600 dark:text-green-400 font-medium">{{ cartStore.coupon.code }}</span>
                                <button @click="removeCoupon" class="text-red-500 dark:text-red-400 text-sm ml-2">Remove</button>
                            </div>
                        </div>
                        <Link :href="route('checkout')" class="block w-full bg-indigo-600 dark:bg-indigo-500 text-white text-center py-3 rounded-lg font-semibold hover:bg-indigo-700 dark:hover:bg-indigo-600 mt-6">Proceed to Checkout</Link>
                        <Link :href="route('shop')" class="block text-center text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 mt-4 text-sm">Continue Shopping</Link>
                    </div>
                </div>
            </div>
        </div>
    </ShopLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useCartStore } from '../../Stores/cart';
import ShopLayout from '../../Layouts/ShopLayout.vue';

const cartStore = useCartStore();
const couponCode = ref('');
const applyingCoupon = ref(false);
const couponError = ref('');

async function updateQuantity(itemId, quantity) {
    if (quantity < 1) {
        await cartStore.removeItem(itemId);
    } else {
        await cartStore.updateItem(itemId, quantity);
    }
}

async function removeItem(itemId) {
    await cartStore.removeItem(itemId);
}

async function applyCoupon() {
    applyingCoupon.value = true;
    couponError.value = '';
    try {
        await cartStore.applyCoupon(couponCode.value);
    } catch (error) {
        couponError.value = error.response?.data?.message || 'Invalid coupon';
    } finally {
        applyingCoupon.value = false;
    }
}

async function removeCoupon() {
    await cartStore.removeCoupon();
}
</script>
