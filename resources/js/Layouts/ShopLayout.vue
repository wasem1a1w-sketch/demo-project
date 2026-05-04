<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors">
        <Notifications />
        <!-- Navigation -->
        <header class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 sticky top-0 z-50 transition-colors">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <!-- Logo -->
                    <Link :href="route('home')" class="flex items-center">
                        <span class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400 bg-clip-text text-transparent">
                            Shop
                        </span>
                    </Link>

                    <!-- Desktop Nav -->
                    <nav class="hidden md:flex items-center space-x-8">
                        <Link :href="route('home')" class="nav-link" :class="isActive('/') ? 'nav-link-active' : ''">
                            Home
                        </Link>
                        <Link :href="route('shop')" class="nav-link" :class="isActive('/shop') ? 'nav-link-active' : ''">
                            Shop
                        </Link>
                        <Link :href="route('categories')" class="nav-link" :class="isActive('/categories') ? 'nav-link-active' : ''">
                            Categories
                        </Link>
                    </nav>

                    <!-- Right Side -->
                    <div class="flex items-center space-x-4">
                        <!-- Theme Toggle -->
                        <ThemeToggle />

                        <!-- Search -->
                        <Link :href="route('shop')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </Link>

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
                        <div class="hidden sm:flex items-center space-x-4">
                            <template v-if="user">
                                <div class="relative" v-click-outside="closeDropdown">
                                    <button @click="userDropdownOpen = !userDropdownOpen" class="flex items-center gap-2 text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 text-sm font-medium">
                                        <span>{{ user.name }}</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    <div v-if="userDropdownOpen" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-100 dark:border-gray-700 py-1 z-50">
                                        <Link :href="route('orders.index')" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">My Orders</Link>
                                        <Link :href="route('addresses.index')" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Manage Addresses</Link>
                                        <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                                        <Link v-if="isAdmin" :href="route('admin.dashboard')" class="block px-4 py-2 text-sm text-indigo-600 dark:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-700">Admin Panel</Link>
                                        <button @click="logout" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50 dark:hover:bg-gray-700">Logout</button>
                                    </div>
                                </div>
                            </template>
                            <template v-else>
                                <Link :href="route('login')" class="text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 font-medium text-sm">
                                    Sign In
                                </Link>
                                <Link :href="route('register')" class="btn-primary">
                                    Sign Up
                                </Link>
                            </template>
                        </div>

                        <!-- Mobile Menu Button -->
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div v-if="mobileMenuOpen" class="md:hidden border-t border-gray-100 dark:border-gray-700">
                <div class="px-4 py-4 space-y-3 bg-white dark:bg-gray-800">
                    <Link :href="route('home')" class="block nav-link" :class="isActive('/') ? 'nav-link-active' : ''" @click="mobileMenuOpen = false">
                        Home
                    </Link>
                    <Link :href="route('shop')" class="block nav-link" :class="isActive('/shop') ? 'nav-link-active' : ''" @click="mobileMenuOpen = false">
                        Shop
                    </Link>
                    <Link :href="route('categories')" class="block nav-link" :class="isActive('/categories') ? 'nav-link-active' : ''" @click="mobileMenuOpen = false">
                        Categories
                    </Link>
                     <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
                         <template v-if="user">
                             <span class="block text-gray-600 dark:text-gray-300 text-sm mb-2">{{ user.name }}</span>
                             <Link :href="route('orders.index')" class="block nav-link mb-2" @click="mobileMenuOpen = false">My Orders</Link>
                             <Link :href="route('addresses.index')" class="block nav-link mb-2" @click="mobileMenuOpen = false">Manage Addresses</Link>
                             <Link v-if="isAdmin" :href="route('admin.dashboard')" class="block text-indigo-600 dark:text-indigo-400 text-sm mb-2" @click="mobileMenuOpen = false">
                                 Admin Panel
                             </Link>
                             <button @click="logout" class="block text-red-500 text-sm">Logout</button>
                         </template>
                         <template v-else>
                             <Link :href="route('login')" class="block nav-link mb-2" @click="mobileMenuOpen = false">Sign In</Link>
                             <Link :href="route('register')" class="block btn-primary text-center" @click="mobileMenuOpen = false">Sign Up</Link>
                         </template>
                     </div>
                </div>
            </div>
        </header>

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
                            <li><Link :href="route('categories')" class="hover:text-white">Categories</Link></li>
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
import Notifications from '../components/Notifications.vue';
import ThemeToggle from '../components/ThemeToggle.vue';

const page = usePage();
const cartStore = useCartStore();
const email = ref('');
const mobileMenuOpen = ref(false);
const userDropdownOpen = ref(false);

const user = computed(() => page.props.auth?.user);
const isAdmin = computed(() => user.value?.is_admin);

onMounted(async () => {
    await cartStore.fetchCart();
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