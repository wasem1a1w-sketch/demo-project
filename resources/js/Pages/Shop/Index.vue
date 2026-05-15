<template>
    <ShopLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Sidebar -->
                <aside class="lg:w-64">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 sticky top-24">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Categories</h3>
                        <ul class="space-y-1">
                            <li>
                                <Link :href="route('shop')" class="block px-3 py-2 rounded-lg text-sm transition-colors" :class="!selectedCategory ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-indigo-600 dark:hover:text-indigo-400'">All Products</Link>
                            </li>
                            <li v-for="category in categories" :key="category.id">
                                <Link :href="route('shop', { category: category.id })" class="block px-3 py-2 rounded-lg text-sm transition-colors" :class="selectedCategory == category.id ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-indigo-600 dark:hover:text-indigo-400'">{{ category.name }}</Link>
                            </li>
                        </ul>
                    </div>
                </aside>

                <!-- Main Content -->
                <div class="flex-1">
                    <div class="flex justify-between items-center mb-8">
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ selectedCategoryName || 'All Products' }}</h1>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ products.total || products.length }} products</span>
                            <select v-model="sortBy" @change="loadProducts" class="input !px-2 text-sm max-w-[160px]">
                                <option value="newest">Newest</option>
                                <option value="price_low">Price: Low to High</option>
                                <option value="price_high">Price: High to Low</option>
                                <option value="name">Name</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="loading" class="flex justify-center py-12">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 dark:border-indigo-400"></div>
                    </div>

                    <div v-else-if="(products.data?.length || products.length) === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">No products found.</div>

                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="product in (products.data || products)" :key="product.id" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                            <Link :href="route('product', product.slug)">
                                <div class="aspect-square bg-gray-100 dark:bg-gray-700 relative">
                                    <img v-if="product.images?.length" :src="`/${product.images[0].image_path}`" :alt="product.name" class="w-full h-full object-contain">
                                    <div v-else class="w-full h-full flex items-center justify-center text-gray-300 dark:text-gray-500">
                                        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <span v-if="product.compare_price && product.compare_price > product.price" class="absolute top-3 left-3 bg-red-500 text-white text-xs px-3 py-1 rounded-full">-{{ Math.round((product.compare_price - product.price) / product.compare_price * 100) }}%</span>
                                    <template v-if="user">
                                        <button @click.prevent="wishlistStore.toggleItem(product.id)" class="absolute top-3 right-3 p-1.5 bg-white dark:bg-gray-800 rounded-full shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                            <svg v-if="wishlistStore.isWishlisted(product.id)" class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                            </svg>
                                            <svg v-else class="w-5 h-5 text-gray-400 hover:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                            </svg>
                                        </button>
                                    </template>
                                </div>
                                <div class="p-5">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ product.category?.name }}</p>
                                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2">{{ product.name }}</h3>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400">${{ product.price }}</span>
                                        <span v-if="product.compare_price && product.compare_price > product.price" class="text-sm text-gray-400 line-through">${{ product.compare_price }}</span>
                                    </div>
                                    <div v-if="product.reviews_count" class="flex items-center gap-1 mt-1.5">
                                        <StarRating :modelValue="Math.round(product.reviews_avg_rating)" :readonly="true" :small="true" />
                                        <span class="text-xs text-gray-400 ml-0.5">({{ product.reviews_count }})</span>
                                    </div>
                                    <p v-if="product.stock <= 0" class="text-xs text-red-500 dark:text-red-400 mt-2">Out of Stock</p>
                                    <p v-else-if="product.stock <= 5" class="text-xs text-orange-500 dark:text-orange-400 mt-2">Only {{ product.stock }} left</p>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ShopLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useCartStore } from '../../Stores/cart';
import { useWishlistStore } from '../../Stores/wishlist';
import ShopLayout from '../../Layouts/ShopLayout.vue';
import StarRating from '../../components/StarRating.vue';

const page = usePage();
const cartStore = useCartStore();
const wishlistStore = useWishlistStore();
const products = ref({ data: [], total: 0 });
const categories = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const sortBy = ref('newest');
const selectedCategory = ref(null);
const selectedCategoryName = ref('');

const user = computed(() => page.props.auth?.user);

let navigateHandler = null;

onMounted(async () => {
    const urlParams = new URLSearchParams(window.location.search);
    selectedCategory.value = urlParams.get('category');
    await loadCategories();
    await loadProducts();
    if (user.value) {
        await wishlistStore.fetchWishlist();
    }

    // Listen for Inertia SPA navigation to update selected category
    navigateHandler = () => {
        const urlParams = new URLSearchParams(window.location.search);
        const newCategory = urlParams.get('category');
        if (newCategory !== selectedCategory.value) {
            selectedCategory.value = newCategory;
            loadProducts();
        }
    };

    document.addEventListener('inertia:navigate', navigateHandler);
});

onUnmounted(() => {
    if (navigateHandler) {
        document.removeEventListener('inertia:navigate', navigateHandler);
    }
});

async function loadCategories() {
    try {
        const response = await axios.get('/api/categories');
        categories.value = response.data.data || response.data || [];
    } catch (error) {
        console.error('Failed to load categories:', error);
    }
}

async function loadProducts() {
    loading.value = true;
    try {
        const response = await axios.get('/api/products', {
            params: {
                category: selectedCategory.value || undefined,
                search: searchQuery.value || undefined,
                sort: sortBy.value,
                _t: Date.now(),
            },
        });
        products.value = response.data;
        if (selectedCategory.value && categories.value.length) {
            const cat = categories.value.find(c => c.id == selectedCategory.value);
            selectedCategoryName.value = cat?.name || '';
        }
    } catch (error) {
        console.error('Failed to load products:', error);
    } finally {
        loading.value = false;
    }
}
</script>