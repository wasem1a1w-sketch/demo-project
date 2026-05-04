<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <Link :href="route('admin.dashboard')" class="flex items-center">
                            <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400">Admin Panel</span>
                        </Link>
                        <nav class="hidden md:flex ml-10 space-x-8">
                            <Link :href="route('admin.dashboard')" class="text-sm font-medium" :class="url === '/admin' ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white'">Dashboard</Link>
                            <Link :href="route('admin.products')" class="text-sm font-medium" :class="url.startsWith('/admin/products') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white'">Products</Link>
                            <Link :href="route('admin.orders')" class="text-sm font-medium" :class="url.startsWith('/admin/orders') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white'">Orders</Link>
                            <Link :href="route('admin.categories')" class="text-sm font-medium" :class="url.startsWith('/admin/categories') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white'">Categories</Link>
                        </nav>
                    </div>
                    <div class="flex items-center space-x-4">
                        <ThemeToggle />
                        <Link :href="route('home')" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 text-sm">View Store</Link>
                        <form @submit.prevent="logout" class="inline">
                            <button type="submit" class="text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300 text-sm">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <slot />
        </main>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ThemeToggle from '../components/ThemeToggle.vue';

const page = usePage();
const url = computed(() => page.url || '');
</script>
