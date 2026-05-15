<template>
    <ShopLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div v-if="loading" class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>
            
            <div v-else-if="!product" class="text-center py-12 text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 rounded-xl">
                Product not found.
            </div>
            
            <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="space-y-4">
                    <div class="aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden">
                        <img v-if="selectedImage" :src="`/${selectedImage}`" :alt="product.name" class="w-full h-full object-contain">
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                            <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div v-if="product.images && product.images.length > 1" class="flex gap-2">
                        <button v-for="(image, index) in product.images" :key="index" @click="selectedImage = image.image_path"
                            :class="selectedImage === image.image_path ? 'ring-2 ring-indigo-600' : ''"
                            class="w-20 h-20 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700">
                            <img :src="`/${image.image_path}`" :alt="image.alt_text" class="w-full h-full object-contain">
                        </button>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6">
                    <nav class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        <Link :href="route('shop')" class="hover:text-indigo-600 dark:hover:text-indigo-400">Shop</Link>
                        <span v-if="product.category"> / {{ product.category.name }}</span>
                        <span> / {{ product.name }}</span>
                    </nav>
                    
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">{{ product.name }}</h1>
                    
                    <div class="flex items-center gap-4 mb-6">
                        <span class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">${{ product.price }}</span>
                        <span v-if="product.compare_price && product.compare_price > product.price" class="text-xl text-gray-400 line-through">${{ product.compare_price }}</span>
                        <span v-if="product.compare_price && product.compare_price > product.price" class="bg-red-500 text-white text-sm px-2 py-1 rounded">
                            -{{ Math.round((product.compare_price - product.price) / product.compare_price * 100) }}% OFF
                        </span>
                    </div>
                    
                    <p v-if="product.short_description" class="text-gray-600 dark:text-gray-300 mb-6">{{ product.short_description }}</p>
                    
                    <div class="mb-6">
                        <span v-if="product.stock > 0" class="text-green-600 dark:text-green-400">In Stock ({{ product.stock }} available)</span>
                        <span v-else class="text-red-500">Out of Stock</span>
                    </div>
                    
                    <div v-if="product.stock > 0" class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Quantity</label>
                        <div class="flex items-center gap-4">
                            <button @click="quantity > 1 && quantity--" class="w-10 h-10 rounded-lg border border-gray-300 dark:border-gray-600 flex items-center justify-center hover:bg-gray-50 dark:hover:bg-gray-700">-</button>
                            <span class="w-12 text-center text-lg text-gray-900 dark:text-white">{{ quantity }}</span>
                            <button @click="quantity++" class="w-10 h-10 rounded-lg border border-gray-300 dark:border-gray-600 flex items-center justify-center hover:bg-gray-50 dark:hover:bg-gray-700">+</button>
                        </div>
                    </div>
                    
                    <div v-if="product.stock > 0" class="flex gap-3 mb-4">
                        <button @click="addToCart" :disabled="adding"
                            class="flex-1 bg-indigo-600 dark:bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 dark:hover:bg-indigo-700 disabled:opacity-50">
                            {{ adding ? 'Adding...' : 'Add to Cart' }}
                        </button>
                        <template v-if="user">
                            <button @click="wishlistStore.toggleItem(product.id)" class="px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg v-if="wishlistStore.isWishlisted(product.id)" class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                                <svg v-else class="w-5 h-5 text-gray-400 hover:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                            </button>
                        </template>
                    </div>
                    
                    <div v-if="message" :class="messageType === 'success' ? 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400'" class="p-4 rounded-lg mb-6">
                        {{ message }}
                    </div>
                    
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Product Description</h3>
                        <p class="text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ product.description }}</p>
                    </div>
                    
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">SKU:</span>
                                <span class="ml-2 text-gray-900 dark:text-gray-300">{{ product.sku || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Category:</span>
                                <span class="ml-2 text-gray-900 dark:text-gray-300">{{ product.category?.name || 'Uncategorized' }}</span>
                            </div>
                            <div v-if="product.weight">
                                <span class="text-gray-500 dark:text-gray-400">Weight:</span>
                                <span class="ml-2 text-gray-900 dark:text-gray-300">{{ product.weight }} {{ product.weight_unit }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <ProductReviews
                v-if="product"
                :productId="product.id"
                :avgRating="product.reviews_avg_rating"
                :reviewsCount="product.reviews_count"
            />
        </div>
    </ShopLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import { useCartStore } from '../../Stores/cart';
import { useWishlistStore } from '../../Stores/wishlist';
import ShopLayout from '../../Layouts/ShopLayout.vue';
import ProductReviews from '../../components/ProductReviews.vue';
import axios from 'axios';

const page = usePage();
const cartStore = useCartStore();
const wishlistStore = useWishlistStore();

const user = computed(() => page.props.auth?.user);

const product = ref(null);
const loading = ref(true);
const selectedImage = ref(null);
const quantity = ref(1);
const adding = ref(false);
const message = ref('');
const messageType = ref('');

// Get slug from URL path
function getSlugFromUrl() {
    const url = page.props.ziggy?.url || window.location.pathname;
    const parts = url.split('/');
    return parts[parts.length - 1] || parts[parts.length - 2];
}

const slug = getSlugFromUrl();

onMounted(async () => {
    if (!slug) {
        loading.value = false;
        return;
    }
    
    try {
        const response = await axios.get(`/api/products/${slug}`);
        product.value = response.data;
        if (product.value.images && product.value.images.length) {
            selectedImage.value = product.value.images[0].image_path;
        }
        if (user.value) {
            await wishlistStore.fetchWishlist();
        }
    } catch (error) {
        console.error('Failed to load product:', error);
    } finally {
        loading.value = false;
    }
});

async function addToCart() {
    adding.value = true;
    message.value = '';
    try {
        await cartStore.addItem(product.value.id, quantity.value);
        message.value = 'Product added to cart!';
        messageType.value = 'success';
    } catch (error) {
        message.value = error.response?.data?.message || 'Failed to add product to cart';
        messageType.value = 'error';
    } finally {
        adding.value = false;
    }
}
</script>