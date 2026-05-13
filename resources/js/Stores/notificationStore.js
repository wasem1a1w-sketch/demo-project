import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

export const useNotificationStore = defineStore('notifications', () => {
    const client = ref({ notifications: [], unreadCount: 0 });
    const admin = ref({ notifications: [], unreadCount: 0 });
    const dropdownOpen = ref(false);
    const echo = ref(null);
    let echoInitialized = false;

    const keyedId = (source, id) => `${source}-${id}`;

    function bucket(scope) {
        return scope === 'admin' ? admin : client;
    }

    function hasUnread(scope) {
        return computed(() => bucket(scope).value.unreadCount > 0);
    }

    function normalizeNotification(n, source) {
        return {
            id: keyedId(source, n.id),
            source,
            dbId: n.id,
            data: {
                type: 'type' in n ? n.type : (n.data?.type || ''),
                message: n.data?.message || '',
                ...n.data,
            },
            created_at: n.created_at,
            read_at: n.read_at ?? null,
        };
    }

    function addNotification(notification) {
        const b = bucket(notification.source);
        const normalized = normalizeNotification(notification, notification.source);
        const exists = b.value.notifications.some(n => n.id === normalized.id);
        if (exists) return;
        b.value.notifications.unshift(normalized);
        b.value.unreadCount++;
    }

    function initEcho(userId, isAdmin = false) {
        if (echoInitialized) return;
        echoInitialized = true;

        window.Pusher = Pusher;

        echo.value = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT,
            wssPort: import.meta.env.VITE_REVERB_PORT,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        });

        echo.value.private(`App.Models.User.${userId}`)
            .listen('ClientNotificationBroadcast', (e) => {
                addNotification({ ...e, source: 'client' });
            });

        if (isAdmin) {
            echo.value.private('admin.notifications')
                .listen('AdminNotificationBroadcast', (e) => {
                    addNotification({ ...e, source: 'admin' });
                });
        }
    }

    function disconnectEcho() {
        if (echo.value) {
            echo.value.disconnect();
            echo.value = null;
        }
        echoInitialized = false;
    }

    async function fetchUnread(scope) {
        const endpoint = scope === 'admin' ? '/admin/notifications/unread' : '/notifications/unread';
        try {
            const response = await axios.get(endpoint);
            const items = Array.isArray(response.data.notifications) ? response.data.notifications : [];
            const b = bucket(scope);
            b.value.unreadCount = response.data.count ?? 0;
            b.value.notifications = items.map(n => normalizeNotification(n, scope));
        } catch (error) {
            console.error(`Failed to fetch ${scope} notifications:`, error);
        }
    }

    async function markAsRead(notification) {
        try {
            const endpoint = notification.source === 'admin'
                ? `/admin/notifications/${notification.dbId}/read`
                : `/notifications/${notification.dbId}/read`;
            await axios.patch(endpoint);
            const b = bucket(notification.source);
            const found = b.value.notifications.find(n => n.id === notification.id);
            if (found) {
                found.read_at = new Date().toISOString();
            }
            b.value.unreadCount = Math.max(0, b.value.unreadCount - 1);
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    }

    async function markAllRead(scope) {
        const endpoint = scope === 'admin' ? '/admin/notifications/read-all' : '/notifications/read-all';
        try {
            await axios.patch(endpoint);
            const b = bucket(scope);
            b.value.notifications.forEach(n => n.read_at = new Date().toISOString());
            b.value.unreadCount = 0;
        } catch (error) {
            console.error(`Failed to mark all ${scope} read:`, error);
        }
    }

    function toggleDropdown() {
        dropdownOpen.value = !dropdownOpen.value;
    }

    function closeDropdown() {
        dropdownOpen.value = false;
    }

    return {
        client,
        admin,
        dropdownOpen,
        hasUnread,
        initEcho,
        disconnectEcho,
        fetchUnread,
        markAsRead,
        markAllRead,
        toggleDropdown,
        closeDropdown,
    };
});
