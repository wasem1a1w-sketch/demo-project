<template>
    <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Exceptions</h1>
            <button @click="throwTestException"
                :disabled="testing"
                class="inline-flex items-center px-3 py-1.5 bg-indigo-600 dark:bg-indigo-500 border border-transparent rounded-md font-semibold text-xs text-white hover:bg-indigo-500 dark:hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 cursor-pointer disabled:opacity-50">
                {{ testing ? 'Recording...' : 'Throw test exception' }}
            </button>
        </div>

        <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-700 dark:text-green-400 text-sm">
            {{ $page.props.flash.success }}
        </div>

        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg mb-6 p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                    <input v-model="filters.search" type="text" placeholder="Class or message..."
                        @keyup.enter="fetchExceptions" class="input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Class</label>
                    <select v-model="filters.class" @change="fetchExceptions" class="input">
                        <option value="">All Classes</option>
                        <option v-for="c in classes" :key="c" :value="c">{{ shortClass(c) }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
                    <input v-model="filters.date_from" type="date" @change="fetchExceptions" class="input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
                    <input v-model="filters.date_to" type="date" @change="fetchExceptions" class="input">
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between">
                <button @click="resetFilters" class="btn-secondary">Reset</button>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ logs.total }} exception{{ logs.total === 1 ? '' : 's' }}</span>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Class</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Message</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Request</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date/Time</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="ex in logs.data" :key="ex.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 max-w-xs truncate" :title="ex.class">
                                    {{ shortClass(ex.class) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white max-w-md truncate" :title="ex.message">
                                {{ ex.message }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono text-xs">
                                {{ formatLocation(ex.file, ex.line) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <span v-if="ex.request_method" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ ex.request_method }}</span>
                                <span v-if="ex.request_url" class="ml-1 text-xs">{{ shortUrl(ex.request_url) }}</span>
                                <span v-else class="text-xs text-gray-400">—</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ ex.user?.name || 'Guest' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ formatDate(ex.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="openModal(ex)"
                                    class="inline-flex items-center px-3 py-1 bg-indigo-600 dark:bg-indigo-500 border border-transparent rounded-md font-semibold text-xs text-white hover:bg-indigo-500 dark:hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 cursor-pointer">
                                    View
                                </button>
                            </td>
                        </tr>
                        <tr v-if="logs.data.length === 0">
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                No exceptions found. Use "Throw test exception" to record one.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="logs.last_page > 1" class="flex justify-center mt-6 gap-2">
            <button v-for="page in logs.last_page" :key="page"
                @click="goToPage(page)"
                :class="page === logs.current_page
                    ? 'bg-indigo-600 text-white px-3 py-1 rounded-md text-sm'
                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-3 py-1 rounded-md text-sm border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'">
                {{ page }}
            </button>
        </div>

        <!-- Exception Detail Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="selected" class="fixed inset-0 z-[70] flex items-center justify-center p-4" @click.self="closeModal">
                    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeModal"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-3xl mx-4 p-6 max-h-[90vh] overflow-y-auto break-words">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Exception Details</h3>
                            <button @click="closeModal" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div v-if="selected" class="space-y-4">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                    {{ selected.class }}
                                </span>
                                <span v-if="selected.environment" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                    {{ selected.environment }}
                                </span>
                            </div>

                            <div>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Message</span>
                                <p class="text-sm text-gray-900 dark:text-white mt-1 whitespace-pre-line leading-relaxed">{{ selected.message }}</p>
                            </div>

                            <div>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Location</span>
                                <p class="text-sm text-gray-900 dark:text-white mt-1 font-mono">{{ formatLocation(selected.file, selected.line) }}</p>
                            </div>

                            <div>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Request</span>
                                <p class="text-sm text-gray-900 dark:text-white mt-1">
                                    <span v-if="selected.request_method" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 mr-1">{{ selected.request_method }}</span>
                                    <span class="font-mono text-xs break-all">{{ selected.request_url || '—' }}</span>
                                </p>
                            </div>

                            <div>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">User</span>
                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ selected.user?.name || 'Guest' }}<span v-if="selected.user?.email" class="text-gray-500 dark:text-gray-400"> ({{ selected.user.email }})</span></p>
                            </div>

                            <div>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Date/Time</span>
                                <p class="text-sm text-gray-900 dark:text-white mt-1">{{ formatDate(selected.created_at) }}</p>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Stack Trace</span>
                                    <button @click="copyTrace"
                                        class="inline-flex items-center px-2.5 py-1 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none transition ease-in-out duration-150 cursor-pointer">
                                        {{ copied ? 'Copied!' : 'Copy trace' }}
                                    </button>
                                </div>
                                <pre class="text-xs text-gray-800 dark:text-gray-100 bg-gray-50 dark:bg-gray-900 rounded-lg p-3 overflow-x-auto max-h-72 overflow-y-auto leading-relaxed font-mono">{{ formattedTrace }}</pre>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button @click="closeModal"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none transition ease-in-out duration-150 cursor-pointer">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    classes: Array,
});

const logs = ref({ data: [], current_page: 1, last_page: 1, total: 0 });
const filters = reactive({
    search: '',
    class: '',
    date_from: '',
    date_to: '',
});

const selected = ref(null);
const copied = ref(false);
const testing = ref(false);

function shortClass(fqcn) {
    const parts = fqcn.split('\\');
    return parts[parts.length - 1];
}

function formatLocation(file, line) {
    if (!file) return '—';
    const short = file.replace(/^\/var\/www\/html\/myproject\//, '');
    return `${short}:${line ?? '?'}`;
}

function shortUrl(url) {
    return url.length > 60 ? url.slice(0, 57) + '...' : url;
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleString();
}

const formattedTrace = computed(() => {
    if (!selected.value) return '';
    const base = selected.value.file ? `${selected.value.file}:${selected.value.line ?? '?'}` : '';
    return (selected.value.trace || [])
        .map((frame, i) => {
            const loc = frame.file ? `${frame.file}:${frame.line ?? '?'}` : 'internal';
            const call = frame.class ? `${frame.class}->${frame.function}()` : frame.function ? `${frame.function}()` : '';
            return `#${i} ${loc}\n    ${call}`;
        })
        .join('\n');
});

async function copyTrace() {
    const text = `${selected.value.class}: ${selected.value.message}\n\n${formattedTrace.value}`;
    try {
        await navigator.clipboard.writeText(text);
    } catch (e) {
        const ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
    }
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
}

function buildParams(page) {
    const params = { page };
    if (filters.search) params.search = filters.search;
    if (filters.class) params.class = filters.class;
    if (filters.date_from) params.date_from = filters.date_from;
    if (filters.date_to) params.date_to = filters.date_to;
    return params;
}

async function fetchExceptions() {
    const res = await axios.get('/admin/exceptions/data', { params: buildParams(1) });
    logs.value = res.data;
}

function goToPage(page) {
    axios.get('/admin/exceptions/data', { params: buildParams(page) }).then(res => {
        logs.value = res.data;
    });
}

function resetFilters() {
    filters.search = '';
    filters.class = '';
    filters.date_from = '';
    filters.date_to = '';
    fetchExceptions();
}

function throwTestException() {
    testing.value = true;
    router.post('/admin/exceptions/test', {}, {
        preserveScroll: true,
        onSuccess: () => {
            testing.value = false;
            fetchExceptions();
        },
        onError: () => {
            testing.value = false;
        },
    });
}

async function openModal(ex) {
    const res = await axios.get(`/admin/exceptions/${ex.id}`);
    selected.value = res.data;
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    selected.value = null;
    document.body.style.overflow = '';
}

onMounted(fetchExceptions);
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-active > div:last-child,
.modal-leave-active > div:last-child {
    transition: transform 0.25s ease, opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-from > div:last-child,
.modal-leave-to > div:last-child {
    transform: scale(0.95);
    opacity: 0;
}
</style>
