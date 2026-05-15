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

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="item in wishlistStore.items" :key="item.id" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                    <Link :href="route('product', item.product?.slug)">
                        <div class="aspect-square bg-gray-100 dark:bg-gray-700 relative">
                            <img v-if="item.product?.image" :src="`/${item.product.image}`" :alt="item.product.name" class="w-full h-full object-contain">
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-300 dark:text-gray-500">
                                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <span v-if="item.product?.compare_price && item.product.compare_price > item.product.price" class="absolute top-3 left-3 bg-red-500 text-white text-xs px-3 py-1 rounded-full">
                                -{{ Math.round((item.product.compare_price - item.product.price) / item.product.compare_price * 100) }}%
                            </span>
                            <button @click.prevent="wishlistStore.removeItem(item.id)" class="absolute top-3 right-3 p-1.5 bg-white dark:bg-gray-800 rounded-full shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                            </button>
                        </div>
                    </Link>
                    <div class="p-5">
                        <Link :href="route('product', item.product?.slug)">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2">{{ item.product?.name }}</h3>
                            <div class="flex items-center gap-2">
                                <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400">${{ item.product?.price }}</span>
                                <span v-if="item.product?.compare_price && item.product.compare_price > item.product.price" class="text-sm text-gray-400 line-through">${{ item.product.compare_price }}</span>
                            </div>
                            <div v-if="item.product?.reviews_count" class="flex items-center gap-1 mt-1.5">
                                <StarRating :modelValue="Math.round(item.product.reviews_avg_rating)" :readonly="true" :small="true" />
                                <span class="text-xs text-gray-400 ml-0.5">({{ item.product.reviews_count }})</span>
                            </div>
                            <p v-if="item.product?.in_stock === false" class="text-xs text-red-500 dark:text-red-400 mt-2">Out of Stock</p>
                        </Link>
                        <button v-if="item.product?.in_stock" @click="addToCart(item.product)" class="mt-3 w-full bg-indigo-600 text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-indigo-700 transition-colors">
                            Add to Cart
                        </button>
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
import StarRating from '../../components/StarRating.vue';

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
