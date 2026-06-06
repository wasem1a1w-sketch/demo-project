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
                            
                            <Dropdown v-if="can('products.read')" :active="url.startsWith('/admin/products') || url.startsWith('/admin/categories') || url.startsWith('/admin/reviews')">
                                <template #trigger>Products</template>
                                <template #content>
                                    <Link :href="route('admin.products')" class="block px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50" :class="url.startsWith('/admin/products') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300'">Products</Link>
                                    <Link :href="route('admin.categories')" class="block px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50" :class="url.startsWith('/admin/categories') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300'">Categories</Link>
                                    <Link v-if="can('reviews.read')" :href="route('admin.reviews')" class="block px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50" :class="url.startsWith('/admin/reviews') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300'">Reviews</Link>
                                </template>
                            </Dropdown>

                            <Dropdown v-if="can('orders.read')" :active="url.startsWith('/admin/orders') || url.startsWith('/admin/coupons') || url.startsWith('/admin/settings')">
                                <template #trigger>Orders</template>
                                <template #content>
                                    <Link :href="route('admin.orders')" class="block px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50" :class="url.startsWith('/admin/orders') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300'">Orders</Link>
                                    <Link v-if="can('coupons.read')" :href="route('admin.coupons')" class="block px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50" :class="url.startsWith('/admin/coupons') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300'">Coupons</Link>
                                    <Link v-if="can('settings.read')" :href="route('admin.settings')" class="block px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50" :class="url.startsWith('/admin/settings') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300'">Settings</Link>
                                </template>
                            </Dropdown>
                            
                            <Dropdown v-if="can('users.read')" :active="url.startsWith('/admin/users') || url.startsWith('/admin/activity-logs')">
                                <template #trigger>Users</template>
                                <template #content>
                                    <Link :href="route('admin.users')" class="block px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50" :class="url.startsWith('/admin/users') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300'">Users</Link>
                                    <Link v-if="can('admin.access')" :href="route('admin.activity-logs')" class="block px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50" :class="url.startsWith('/admin/activity-logs') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300'">Activity Logs</Link>
                                </template>
                            </Dropdown>
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

                            <div v-if="can('products.read')" class="space-y-1">
                                <p class="px-3 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mt-4 mb-1">Products</p>
                                <Link :href="route('admin.products')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-colors" :class="url.startsWith('/admin/products') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'" @click="mobileMenuOpen = false">
                                    <span class="ml-7">Products</span>
                                </Link>
                                <Link :href="route('admin.categories')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-colors" :class="url.startsWith('/admin/categories') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'" @click="mobileMenuOpen = false">
                                    <span class="ml-7">Categories</span>
                                </Link>
                                <Link v-if="can('reviews.read')" :href="route('admin.reviews')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-colors" :class="url.startsWith('/admin/reviews') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'" @click="mobileMenuOpen = false">
                                    <span class="ml-7">Reviews</span>
                                </Link>
                            </div>

                            <div v-if="can('orders.read')" class="space-y-1">
                                <p class="px-3 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mt-4 mb-1">Orders</p>
                                <Link :href="route('admin.orders')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-colors" :class="url.startsWith('/admin/orders') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'" @click="mobileMenuOpen = false">
                                    <span class="ml-7">Orders</span>
                                </Link>
                                <Link v-if="can('coupons.read')" :href="route('admin.coupons')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-colors" :class="url.startsWith('/admin/coupons') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'" @click="mobileMenuOpen = false">
                                    <span class="ml-7">Coupons</span>
                                </Link>
                                <Link v-if="can('settings.read')" :href="route('admin.settings')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-colors" :class="url.startsWith('/admin/settings') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'" @click="mobileMenuOpen = false">
                                    <span class="ml-7">Settings</span>
                                </Link>
                            </div>

                            <div v-if="can('users.read')" class="space-y-1">
                                <p class="px-3 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mt-4 mb-1">Users</p>
                                <Link :href="route('admin.users')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-colors" :class="url.startsWith('/admin/users') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'" @click="mobileMenuOpen = false">
                                    <span class="ml-7">Users</span>
                                </Link>
                                <Link v-if="can('admin.access')" :href="route('admin.activity-logs')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-colors" :class="url.startsWith('/admin/activity-logs') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'" @click="mobileMenuOpen = false">
                                    <span class="ml-7">Activity Logs</span>
                                </Link>
                            </div>
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
import { ref, computed, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { usePermission } from '../composables/usePermission';
import { useNotification } from '../composables/useNotification';
import NotificationBell from '../components/NotificationBell.vue';
import ThemeToggle from '../components/ThemeToggle.vue';
import Notifications from '../components/Notifications.vue';
import Dropdown from '../components/Dropdown.vue';

const { can } = usePermission();
const { error, success } = useNotification();

const page = usePage();
const url = computed(() => page.url || '');
const mobileMenuOpen = ref(false);

watch(() => page.props, (props) => {
    if (props.flash?.error) {
        error(props.flash.error);
    }
    if (props.flash?.success) {
        success(props.flash.success);
    }
}, { deep: true, immediate: true });

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
