<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors">
        <Notifications />
        <!-- Navigation -->
        <header class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 sticky top-0 z-50 transition-colors">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <div class="flex items-center">
                        <button @click="mobileMenuOpen = true" class="md:hidden p-1.5 mr-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        </button>
                        <Link :href="route('home')" class="flex items-center">
                            <span class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400 bg-clip-text text-transparent">
                                Shop
                            </span>
                        </Link>
                    </div>

                    <!-- Desktop Nav -->
                    <nav class="hidden md:flex items-center space-x-8">
                        <Link :href="route('home')" class="nav-link" :class="isActive('/') ? 'nav-link-active' : ''">
                            Home
                        </Link>
                        <Link :href="route('shop')" class="nav-link" :class="isActive('/shop') ? 'nav-link-active' : ''">
                            Shop
                        </Link>
                        <!-- <Link :href="route('categories')" class="nav-link" :class="isActive('/categories') ? 'nav-link-active' : ''">
                            Categories
                        </Link> -->
                    </nav>

                    <!-- Right Side -->
                    <div class="flex items-center space-x-2 sm:space-x-4">
                        <!-- Theme Toggle -->
                        <ThemeToggle />

                        <!-- Search -->
                        <Link :href="route('shop')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </Link>

                        <!-- Notifications -->
                        <template v-if="user">
                            <NotificationBell />
                        </template>

                        <!-- Wishlist -->
                        <template v-if="user">
                            <Link :href="route('wishlist')" class="relative text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                                <span v-if="wishlistStore.itemCount > 0" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                    {{ wishlistStore.itemCount }}
                                </span>
                            </Link>
                        </template>

                        <!-- Cart -->
                        <Link :href="route('cart')" class="relative text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span v-if="cartStore.itemCount > 0" class="absolute -top-2 -right-2 bg-indigo-600 dark:bg-indigo-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                {{ cartStore.itemCount }}
                            </span>
                        </Link>

                        <!-- Auth -->
                        <div class="hidden sm:flex items-center space-x-2 sm:space-x-4">
                            <template v-if="user">
                                <div class="relative" v-click-outside="closeDropdown">
                                    <button @click="userDropdownOpen = !userDropdownOpen" class="flex items-center gap-2 text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 text-sm font-medium whitespace-nowrap">
                                        <span>{{ user.name }}</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    <div v-if="userDropdownOpen" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-100 dark:border-gray-700 py-1 z-50">
                                        <Link :href="route('orders.index')" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">My Orders</Link>
                                        <Link :href="route('addresses.index')" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Manage Addresses</Link>
                                        <Link :href="route('wishlist')" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">My Wishlist</Link>
                                        <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                                        <Link v-if="can('admin.access')" :href="route('admin.dashboard')" class="block px-4 py-2 text-sm text-indigo-600 dark:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-700">Admin Panel</Link>
                                        <button @click="logout" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50 dark:hover:bg-gray-700">Logout</button>
                                    </div>
                                </div>
                            </template>
                            <template v-else>
                                <Link :href="route('login')" class="text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 font-medium text-sm whitespace-nowrap">
                                    Sign In
                                </Link>
                                <Link :href="route('register')" class="btn-primary whitespace-nowrap">
                                    Sign Up
                                </Link>
                            </template>
                        </div>
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
                            <span class="text-lg font-bold bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400 bg-clip-text text-transparent">Menu</span>
                            <button @click="mobileMenuOpen = false" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                            <Link :href="route('home')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-colors" :class="page.url === '/' ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'" @click="mobileMenuOpen = false">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                <span>Home</span>
                            </Link>
                            <Link :href="route('shop')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-colors" :class="isActive('/shop') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'" @click="mobileMenuOpen = false">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                <span>Shop</span>
                            </Link>
                            <!-- <Link :href="route('categories')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-colors" :class="isActive('/categories') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'" @click="mobileMenuOpen = false">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                <span>Categories</span>
                            </Link> -->
                        </nav>
                        <div class="border-t border-gray-200 dark:border-gray-700 px-3 py-4 space-y-1">
                            <template v-if="user">
                                <div class="flex items-center gap-3 px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span class="font-medium truncate">{{ user.name }}</span>
                                </div>
                                <Link :href="route('orders.index')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" @click="mobileMenuOpen = false">
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                    <span>My Orders</span>
                                </Link>
                                <Link :href="route('addresses.index')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" @click="mobileMenuOpen = false">
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    <span>Addresses</span>
                                </Link>
                                <Link :href="route('wishlist')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" @click="mobileMenuOpen = false">
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                    <span>My Wishlist</span>
                                </Link>
                                <Link v-if="can('admin.access')" :href="route('admin.dashboard')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors" @click="mobileMenuOpen = false">
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"></path></svg>
                                    <span>Admin Panel</span>
                                </Link>
                                <div class="border-t border-gray-200 dark:border-gray-700 pt-2">
                                    <button @click="logout" class="flex items-center gap-3 w-full px-3 py-3 rounded-xl text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                        <span>Logout</span>
                                    </button>
                                </div>
                            </template>
                            <template v-else>
                                <Link :href="route('login')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" @click="mobileMenuOpen = false">
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                                    <span>Sign In</span>
                                </Link>
                                <Link :href="route('register')" class="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors" @click="mobileMenuOpen = false">
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                                    <span>Sign Up</span>
                                </Link>
                            </template>
                        </div>
                    </aside>
                </div>
            </Transition>
        </Teleport>

        <!-- Main Content -->
        <main>
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-gray-900 dark:bg-gray-950 text-white py-16 mt-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
                    <div>
                        <span class="text-2xl font-bold bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">
                            Shop
                        </span>
                        <p class="mt-4 text-gray-400 text-sm">Your trusted destination for premium products.</p>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-4">Shop</h4>
                        <ul class="space-y-3 text-gray-400 text-sm">
                            <li><Link :href="route('shop')" class="hover:text-white">All Products</Link></li>
                            <!-- <li><Link :href="route('categories')" class="hover:text-white">Categories</Link></li> -->
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-4">Support</h4>
                        <ul class="space-y-3 text-gray-400 text-sm">
                            <li><a href="#" class="hover:text-white">Contact</a></li>
                            <li><a href="#" class="hover:text-white">Shipping</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-4">Newsletter</h4>
                        <form @submit.prevent="subscribe" class="flex">
                            <input v-model="email" type="email" placeholder="Your email" class="bg-gray-800 dark:bg-gray-900 px-4 py-2 rounded-l-lg text-white w-full focus:outline-none focus:ring-2 focus:ring-indigo-500 border border-gray-700 dark:border-gray-600">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded-r-lg transition-colors">Subscribe</button>
                        </form>
                    </div>
                </div>
                <div class="border-t border-gray-800 dark:border-gray-700 mt-12 pt-8 text-center text-gray-500 text-sm">
                    &copy; {{ new Date().getFullYear() }} Shop. All rights reserved.
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { useCartStore } from '../Stores/cart';
import { useWishlistStore } from '../Stores/wishlist';
import { usePermission } from '../composables/usePermission';
import Notifications from '../components/Notifications.vue';
import NotificationBell from '../components/NotificationBell.vue';
import ThemeToggle from '../components/ThemeToggle.vue';

const { can } = usePermission();

const page = usePage();
const cartStore = useCartStore();
const wishlistStore = useWishlistStore();
const email = ref('');
const mobileMenuOpen = ref(false);
const userDropdownOpen = ref(false);

const user = computed(() => page.props.auth?.user);

onMounted(async () => {
    await cartStore.fetchCart();
    if (user.value) {
        await wishlistStore.fetchWishlist();
    }
});

function isActive(url) {
    return page.url === url || page.url.startsWith(url);
}

function subscribe() {
    email.value = '';
}

function logout() {
    router.post(route('logout'));
}

function closeDropdown() {
    userDropdownOpen.value = false;
}

const vClickOutside = {
    mounted(el, binding) {
        el.clickOutsideEvent = (event) => {
            if (!(el === event.target || el.contains(event.target))) {
                binding.value();
            }
        };
        document.addEventListener('click', el.clickOutsideEvent);
    },
    unmounted(el) {
        document.removeEventListener('click', el.clickOutsideEvent);
    },
};
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