<template>
    <div class="relative mx-2" v-click-outside="notificationStore.closeDropdown">
        <button @click="notificationStore.toggleDropdown"
            class="p-2 rounded-xl text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-all duration-200"
            :class="{ 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20': unreadCount > 0 }">
            <svg class="w-5 h-5" :class="{ 'animate-ring': ringing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span v-if="unreadCount > 0"
                class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1 ring-2 ring-white dark:ring-gray-800 shadow-sm">
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <Transition name="dropdown">
            <div v-if="notificationStore.dropdownOpen"
                class="absolute right-0 mt-2 w-80 sm:w-96 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 z-50 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-800/50">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        {{ scope === 'admin' ? 'Admin Notifications' : 'Notifications' }}
                    </h3>
                    <button v-if="unreadCount > 0" @click="notificationStore.markAllRead(scope)"
                        class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors">
                        Mark all read
                    </button>
                </div>

                <div class="max-h-[400px] overflow-y-auto">
                    <div v-if="notifications.length === 0"
                        class="py-12 px-4 text-center">
                        <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                        </div>
                        <p class="text-sm text-gray-400 dark:text-gray-500">No notifications yet</p>
                    </div>

                    <button v-for="notification in notifications" :key="notification.id"
                        @click="handleClick(notification)"
                        class="w-full text-left px-4 py-3.5 hover:bg-gray-50 dark:hover:bg-gray-700/30 border-b border-gray-50 dark:border-gray-700/30 last:border-0 transition-colors duration-150"
                        :class="{ 'bg-indigo-50/40 dark:bg-indigo-900/10': !notification.read_at }">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 shrink-0">
                                <div v-if="notification.data.type === 'new_order'"
                                    class="w-9 h-9 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center">
                                    <svg class="w-4.5 h-4.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div v-else-if="notification.data.type === 'order_status_changed'"
                                    class="w-9 h-9 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                                    <svg class="w-4.5 h-4.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                </div>
                                <div v-else-if="notification.data.type === 'new_user_registered'"
                                    class="w-9 h-9 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                                    <svg class="w-4.5 h-4.5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                </div>
                                <div v-else-if="notification.data.type === 'low_stock'"
                                    class="w-9 h-9 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
                                    <svg class="w-4.5 h-4.5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div v-else-if="notification.data.type === 'review_status_changed'"
                                    class="w-9 h-9 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                                    <svg class="w-4.5 h-4.5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div v-else-if="notification.data.type === 'review_submitted'"
                                    class="w-9 h-9 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center">
                                    <svg class="w-4.5 h-4.5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                    </svg>
                                </div>
                                <div v-else
                                    class="w-9 h-9 bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center">
                                    <svg class="w-4.5 h-4.5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900 dark:text-white leading-snug">
                                    {{ notification.data.message }}
                                </p>
                                <div class="flex items-center gap-2 mt-1.5">
                                    <p class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ timeAgo(notification.created_at) }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="!notification.read_at" class="shrink-0 mt-1.5">
                                <span class="block w-2 h-2 bg-indigo-500 rounded-full shadow-sm shadow-indigo-500/30"></span>
                            </div>
                        </div>
                    </button>
                </div>

                <div v-if="notifications.length > 0" class="px-4 py-2.5 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 text-center">
                        {{ unreadCount }} unread · {{ notifications.length }} total
                    </p>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useNotificationStore } from '../Stores/notificationStore';

const props = defineProps({
    scope: {
        type: String,
        default: 'client',
        validator: v => ['client', 'admin'].includes(v),
    },
});

const notificationStore = useNotificationStore();
const page = usePage();
const ringing = ref(false);

const user = computed(() => page.props.auth?.user);

const b = computed(() => props.scope === 'admin' ? notificationStore.admin : notificationStore.client);
const notifications = computed(() => b.value.notifications);
const unreadCount = computed(() => b.value.unreadCount);

watch(unreadCount, (newVal, oldVal) => {
    if (newVal > oldVal) {
        ringing.value = true;
        setTimeout(() => { ringing.value = false; }, 600);
    }
});

onMounted(async () => {
    if (user.value) {
        await notificationStore.fetchUnread(props.scope);
        notificationStore.initEcho(user.value.id, user.value.permissions?.includes('admin.access'));
    }
});

onUnmounted(() => {
    // don't disconnect Echo — it's shared between scopes for the same session
});

function handleClick(notification) {
    if (!notification.read_at) {
        notificationStore.markAsRead(notification);
    }
    notificationStore.closeDropdown();
}

function timeAgo(date) {
    const seconds = Math.floor((new Date() - new Date(date)) / 1000);
    if (seconds < 60) return 'just now';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    return `${days}d ago`;
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
.dropdown-enter-active,
.dropdown-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-6px) scale(0.98);
}

@keyframes ring {
    0%, 100% { transform: rotate(0); }
    15% { transform: rotate(12deg); }
    30% { transform: rotate(-10deg); }
    45% { transform: rotate(8deg); }
    60% { transform: rotate(-6deg); }
    75% { transform: rotate(4deg); }
    90% { transform: rotate(-2deg); }
}
.animate-ring {
    animation: ring 0.6s ease-in-out;
    transform-origin: top center;
}
</style>
