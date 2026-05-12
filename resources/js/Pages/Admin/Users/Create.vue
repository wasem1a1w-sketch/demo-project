<template>
    <div class="px-4 py-6 sm:px-0">
        <div class="max-w-2xl">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Create User</h1>

            <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name *</label>
                            <input v-model="form.name" type="text" required
                                class="mt-1.5 block w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email *</label>
                            <input v-model="form.email" type="email" required
                                class="mt-1.5 block w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password *</label>
                            <input v-model="form.password" type="password" required minlength="8"
                                class="mt-1.5 block w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
                            <select v-model="form.role"
                                class="mt-1.5 block w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                                <option value="">No role</option>
                                <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700 flex gap-3">
                    <button type="submit" class="bg-indigo-600 text-white px-5 py-2 rounded-lg hover:bg-indigo-700 font-medium text-sm transition">
                        Create User
                    </button>
                    <Link :href="route('admin.users')" class="text-gray-700 dark:text-gray-300 px-5 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 font-medium text-sm transition">
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useNotification } from '../../../composables/useNotification';

const { success: notifySuccess, error: notifyError } = useNotification();

defineProps({
    roles: Array,
});

const form = reactive({
    name: '',
    email: '',
    password: '',
    role: '',
});

function submit() {
    router.post(route('admin.users.store'), form, {
        onSuccess: () => {
            notifySuccess('User created successfully.');
            router.visit(route('admin.users'));
        },
        onError: (errors) => notifyError(Object.values(errors)[0] || 'Validation failed.'),
    });
}
</script>
