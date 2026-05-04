import { createApp, h } from 'vue';
import { createPinia } from 'pinia';
import { createInertiaApp } from '@inertiajs/vue3';
import { ZiggyVue } from 'ziggy-js';
import axios from 'axios';
import '../css/app.css';
import { useCartStore } from './Stores/cart';
import AdminLayout from './Layouts/AdminLayout.vue';

const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content;

if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
    axios.defaults.withCredentials = true;
}

const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

function initTheme() {
    const stored = localStorage.getItem('theme');
    const isDark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (isDark) {
        document.documentElement.classList.add('dark');
    }
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        for (const path in pages) {
            if (path.endsWith(`/${name}.vue`)) {
                const page = pages[path];
                if (name.startsWith('Admin/') && !page.default.layout) {
                    page.default.layout = AdminLayout;
                }
                return page;
            }
        }
        throw new Error(`Page not found: ${name}`);
    },
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();

        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue);

        // Fetch cart data on app initialization
        const cartStore = useCartStore();
        cartStore.fetchCart();

        app.mount(el);

        initTheme();
    },
});