<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Navigation -->
        <header class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 sticky top-0 z-50 transition-colors">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <Link :href="route('home')" class="flex items-center">
                        <span class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400 bg-clip-text text-transparent">Shop</span>
                    </Link>
                    <nav class="hidden md:flex items-center space-x-8">
                        <Link :href="route('home')" class="nav-link nav-link-active">Home</Link>
                        <Link :href="route('shop')" class="nav-link">Shop</Link>
                        <Link :href="route('categories')" class="nav-link">Categories</Link>
                    </nav>
                    <div class="flex items-center space-x-4">
                        <ThemeToggle />
                        <Link :href="route('cart')" class="relative text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span v-if="cartStore.itemCount > 0" class="absolute -top-2 -right-2 bg-indigo-600 dark:bg-indigo-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">{{ cartStore.itemCount }}</span>
                        </Link>
                        <template v-if="user">
                            <span class="text-gray-600 dark:text-gray-300 text-sm hidden sm:block">{{ user.name }}</span>
                            <Link v-if="isAdmin" :href="route('admin.dashboard')" class="text-indigo-600 dark:text-indigo-400 text-sm hover:text-indigo-700 dark:hover:text-indigo-300">Admin</Link>
                            <button @click="logout" class="text-red-500 dark:text-red-400 text-sm hover:text-red-600 dark:hover:text-red-300">Logout</button>
                        </template>
                        <template v-else>
                            <Link :href="route('login')" class="hidden sm:block text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 text-sm">Sign In</Link>
                            <Link :href="route('register')" class="btn-primary text-sm">Sign Up</Link>
                        </template>
                    </div>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-700 dark:to-purple-700 text-white py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-6">Discover Premium Products</h1>
                <p class="text-xl mb-8 text-indigo-100">Shop the latest collection at unbeatable prices.</p>
                <Link :href="route('shop')" class="inline-block bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">Shop Now</Link>
            </div>
        </section>

        <!-- Features -->
        <section class="py-16 bg-gray-50 dark:bg-gray-800 transition-colors">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div class="text-center">
                        <div class="w-14 h-14 bg-white dark:bg-gray-700 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-sm">
                            <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Quality Products</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Handpicked for excellence</p>
                    </div>
                    <div class="text-center">
                        <div class="w-14 h-14 bg-white dark:bg-gray-700 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-sm">
                            <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Best Prices</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Price match guarantee</p>
                    </div>
                    <div class="text-center">
                        <div class="w-14 h-14 bg-white dark:bg-gray-700 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-sm">
                            <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Fast Delivery</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Free shipping over $100</p>
                    </div>
                    <div class="text-center">
                        <div class="w-14 h-14 bg-white dark:bg-gray-700 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-sm">
                            <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">24/7 Support</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Dedicated support</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Featured Products -->
        <section class="py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center mb-12 text-gray-900 dark:text-white">Featured Products</h2>

                <div v-if="loading" class="flex justify-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 dark:border-indigo-400"></div>
                </div>

                <div v-else-if="products.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">No products available.</div>

                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div v-for="product in products" :key="product.id" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-all overflow-hidden">
                        <Link :href="route('product', product.slug)">
                            <div class="aspect-square bg-gray-100 dark:bg-gray-700 relative">
                                <img v-if="product.images && product.images.length" :src="`/${product.images[0].image_path}`" :alt="product.name" class="w-full h-full object-contain">
                                <div v-else class="w-full h-full flex items-center justify-center text-gray-300 dark:text-gray-500">
                                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                                <span v-if="product.compare_price && product.compare_price > product.price" class="absolute top-3 left-3 bg-red-500 text-white text-xs px-3 py-1 rounded-full">-{{ Math.round((product.compare_price - product.price) / product.compare_price * 100) }}%</span>
                            </div>
                            <div class="p-5">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ product.category?.name }}</p>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">{{ product.name }}</h3>
                                <div class="flex items-center gap-2">
                                    <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400">${{ product.price }}</span>
                                    <span v-if="product.compare_price && product.compare_price > product.price" class="text-sm text-gray-400 line-through">${{ product.compare_price }}</span>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>

                <div class="text-center mt-12">
                    <Link :href="route('shop')" class="inline-block bg-gray-900 dark:bg-gray-700 text-white px-8 py-3 rounded-lg font-medium hover:bg-gray-800 dark:hover:bg-gray-600 transition-colors">View All Products</Link>
                </div>
            </div>
        </section>

        <!-- Categories -->
        <section class="py-20 bg-gray-50 dark:bg-gray-800 transition-colors">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center mb-12 text-gray-900 dark:text-white">Shop by Category</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                    <Link v-for="category in categories" :key="category.id" :href="route('shop', { category: category.id })" class="bg-white dark:bg-gray-700 rounded-xl p-6 text-center hover:-translate-y-1 transition-transform shadow-sm">
                        <div class="text-3xl mb-2">📦</div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ category.name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ category.products_count }} products</p>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gray-900 dark:bg-gray-950 text-white py-12 transition-colors">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <span class="text-2xl font-bold bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">Shop</span>
                <p class="text-gray-500 dark:text-gray-400 mt-4">&copy; {{ new Date().getFullYear() }} Shop. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import { useCartStore } from '../Stores/cart';
import ThemeToggle from '../components/ThemeToggle.vue';

const page = usePage();
const cartStore = useCartStore();
const products = ref([]);
const categories = ref([]);
const loading = ref(true);

const user = computed(() => page.props.auth?.user);
const isAdmin = computed(() => user.value?.is_admin);

onMounted(async () => {
    try {
        const [productsRes, categoriesRes] = await Promise.all([
            axios.get('/api/products?featured=1&per_page=8'),
            axios.get('/api/categories')
        ]);
        products.value = productsRes.data.data || [];
        categories.value = categoriesRes.data.data || categoriesRes.data || [];
    } catch (error) {
        console.error('Failed to load data:', error);
    } finally {
        loading.value = false;
    }
});

function logout() {
    router.post(route('logout'));
}
</script>