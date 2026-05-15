<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <button @click="mobileMenuOpen = true" class="md:hidden p-1.5 mr-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        </button>
                        <Link :href="route('admin.dashboard')" class="flex items-center">
                            <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400">Admin Panel</span>
                        </Link>
                        <nav class="hidden md:flex ml-10 space-x-8">
                            <Link :href="route('admin.dashboard')" class="text-sm font-medium whitespace-nowrap" :class="url === '/admin' ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white'">Dashboard</Link>
                            <Link v-if="can('products.read')" :href="route('admin.products')" class="text-sm font-medium whitespace-nowrap" :class="url.startsWith('/admin/products') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white'">Products</Link>
                            <Link v-if="can('orders.read')" :href="route('admin.orders')" class="text-sm font-medium whitespace-nowrap" :class="url.startsWith('/admin/orders') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white'">Orders</Link>
                            <Link v-if="can('categories.read')" :href="route('admin.categories')" class="text-sm font-medium whitespace-nowrap" :class="url.startsWith('/admin/categories') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white'">Categories</Link>
                            <Link v-if="can('users.read')" :href="route('admin.users')" class="text-sm font-medium whitespace-nowrap" :class="url.startsWith('/admin/users') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white'">Users</Link>
                            <Link v-if="can('reviews.read')" :href="route('admin.reviews')" class="text-sm font-medium whitespace-nowrap" :class="url.startsWith('/admin/reviews') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white'">Reviews</Link>
                        </nav>
                    </div>
                    <div class="flex items-center space-x-2 sm:space-x-4">
                        <NotificationBell scope="admin" />
                        <ThemeToggle />
                        <Link :href="route('home')" class="hidden sm:inline-flex text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 text-sm">View Store</Link>
                        <form @submit.prevent="logout" class="hidden sm:block">
                            <button type="submit" class="text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300 text-sm">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <Teleport to="body">
            <Transition name="sidebar">
                <div v-if="mobileMenuOpen" class="fixed inset-0 z-[60] md:hidden">
                    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="mobileMenuOpen = false"></div>
                    <aside class="fixed top-0 left-0 bottom-0 w-72 bg-white dark:bg-gray-800 shadow-2xl flex flex-col">
                        <div class="flex items-center justify-between px-5 h-16 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">Menu</span>
                            <button @click="mobileMenuOpen = false" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                            <Link :href="route('admin.dashboard')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-colors" :class="url === '/admin' ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'" @click="mobileMenuOpen = false">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                <span>Dashboard</span>
                            </Link>
                            <Link v-if="can('products.read')" :href="route('admin.products')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-colors" :class="url.startsWith('/admin/products') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'" @click="mobileMenuOpen = false">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4l-8-4m-8 4l8 4"></path></svg>
                                <span>Products</span>
                            </Link>
                            <Link v-if="can('orders.read')" :href="route('admin.orders')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-colors" :class="url.startsWith('/admin/orders') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'" @click="mobileMenuOpen = false">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                <span>Orders</span>
                            </Link>
                            <Link v-if="can('categories.read')" :href="route('admin.categories')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-colors" :class="url.startsWith('/admin/categories') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'" @click="mobileMenuOpen = false">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                <span>Categories</span>
                            </Link>
                            <Link v-if="can('users.read')" :href="route('admin.users')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-colors" :class="url.startsWith('/admin/users') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'" @click="mobileMenuOpen = false">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                <span>Users</span>
                            </Link>
                            <Link v-if="can('reviews.read')" :href="route('admin.reviews')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-colors" :class="url.startsWith('/admin/reviews') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'" @click="mobileMenuOpen = false">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                                <span>Reviews</span>
                            </Link>
                        </nav>
                        <div class="border-t border-gray-200 dark:border-gray-700 px-3 py-4 space-y-1">
                            <Link :href="route('home')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" @click="mobileMenuOpen = false">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                <span>View Store</span>
                            </Link>
                            <button @click="logout" class="flex items-center gap-3 w-full px-3 py-3 rounded-xl text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                <span>Logout</span>
                            </button>
                        </div>
                    </aside>
                </div>
            </Transition>
        </Teleport>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <slot />
        </main>
    </div>
    <Notifications />
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { usePermission } from '../composables/usePermission';
import NotificationBell from '../components/NotificationBell.vue';
import ThemeToggle from '../components/ThemeToggle.vue';
import Notifications from '../components/Notifications.vue';

const { can } = usePermission();

const page = usePage();
const url = computed(() => page.url || '');
const mobileMenuOpen = ref(false);

function logout() {
    router.post(route('logout'));
}
</script>

<style scoped>
.sidebar-enter-active,
.sidebar-leave-active {
    transition: opacity 0.2s ease;
}
.sidebar-enter-active aside,
.sidebar-leave-active aside {
    transition: transform 0.25s ease;
}
.sidebar-enter-from,
.sidebar-leave-to {
    opacity: 0;
}
.sidebar-enter-from aside,
.sidebar-leave-to aside {
    transform: translateX(-100%);
}
</style>
