<template>
    <ShopLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">My Wishlist</h1>

            <div v-if="wishlistStore.loading" class="flex justify-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 dark:border-indigo-400"></div>
            </div>

            <div v-else-if="wishlistStore.items.length === 0" class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl">
                <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
                <p class="text-gray-500 dark:text-gray-400 mb-4">Your wishlist is empty.</p>
                <Link :href="route('shop')" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-colors">Browse Products</Link>
            </div>

            <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                <div v-for="item in wishlistStore.items" :key="item.id" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                    <Link :href="route('product', item.product?.slug)">
                        <div class="aspect-square bg-gray-100 dark:bg-gray-700 relative">
                            <img v-if="item.product?.image" :src="`/${item.product.image}`" :alt="item.product.name" class="w-full h-full object-contain">
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-300 dark:text-gray-500">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <button @click.prevent="wishlistStore.removeItem(item.id)" class="absolute top-2 right-2 p-1 bg-white dark:bg-gray-800 rounded-full shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                            </button>
                        </div>
                    </Link>
                    <div class="p-3">
                        <Link :href="route('product', item.product?.slug)">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1 line-clamp-2">{{ item.product?.name }}</h3>
                            <p class="text-base font-bold text-indigo-600 dark:text-indigo-400 mb-2">${{ item.product?.price }}</p>
                        </Link>
                        <button v-if="item.product?.in_stock" @click="addToCart(item.product)" class="w-full bg-indigo-600 text-white py-1.5 rounded-lg text-xs font-semibold hover:bg-indigo-700 transition-colors">
                            Add to Cart
                        </button>
                        <p v-else class="text-center text-xs text-red-500 py-1.5">Out of Stock</p>
                    </div>
                </div>
            </div>
        </div>
    </ShopLayout>
</template>

<script setup>
import { onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useWishlistStore } from '../../Stores/wishlist';
import { useCartStore } from '../../Stores/cart';
import ShopLayout from '../../Layouts/ShopLayout.vue';

const wishlistStore = useWishlistStore();
const cartStore = useCartStore();

onMounted(() => {
    wishlistStore.fetchWishlist();
});

async function addToCart(product) {
    if (!product) return;
    await cartStore.addItem(product.id, 1);
}
</script>
