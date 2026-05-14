<template>
    <div class="px-4 py-6 sm:px-0">
        <!-- Tabs -->
        <div class="flex gap-1 mb-6 border-b border-gray-200 dark:border-gray-700">
            <button @click="activeTab = 'users'" :class="tabClass('users')" class="px-4 py-2.5 text-sm font-medium transition-colors">
                Users
            </button>
            <button @click="activeTab = 'roles'" :class="tabClass('roles')" class="px-4 py-2.5 text-sm font-medium transition-colors">
                Roles
            </button>
        </div>

        <!-- Users Tab -->
        <template v-if="activeTab === 'users'">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Users</h1>
                <div class="flex flex-col sm:flex-row gap-3">
                    <select v-model="roleFilter" @change="applyFilter" class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        <option value="">All Users</option>
                        <option value="client">Clients</option>
                        <option value="non-client">Non-Clients</option>
                    </select>
                    <Link v-if="can('users.create')" :href="route('admin.users.create')" class="btn-primary inline-flex items-center justify-center">
                        Create User
                    </Link>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Joined</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="user in users.data" :key="user.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ user.name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ user.email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="user.roles.length" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-400">
                                        {{ user.roles[0].name }}
                                    </span>
                                    <span v-else class="text-sm text-gray-400 dark:text-gray-500">—</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ formatDate(user.created_at) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link v-if="can('users.update')" :href="route('admin.users.edit', { user: user.id })" class="inline-flex items-center px-3 py-1 bg-indigo-600 dark:bg-indigo-500 border border-transparent rounded-md font-semibold text-xs text-white dark:text-indigo-100 hover:bg-indigo-500 dark:hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 mr-3">Edit</Link>
                                    <button v-if="can('users.delete')" @click="confirmDeleteUser(user.id)" class="inline-flex items-center px-3 py-1 bg-red-600 dark:bg-red-500 border border-transparent rounded-md font-semibold text-xs text-white dark:text-red-100 hover:bg-red-500 dark:hover:bg-red-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No users found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :meta="users" />
            </div>
        </template>

        <!-- Roles Tab -->
        <template v-if="activeTab === 'roles'">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Roles</h1>
                <Link v-if="can('roles.create')" :href="route('admin.users.roles.create')" class="btn-primary">
                    Create Role
                </Link>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Users</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="role in allRoles" :key="role.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ role.name }}</span>
                                        <span v-if="role.name === 'admin' || role.name === 'client'" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">System</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ role.users_count }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link v-if="can('roles.update') && role.name !== 'admin' && role.name !== 'client'" :href="route('admin.users.roles.edit', { role: role.id })" class="inline-flex items-center px-3 py-1 bg-indigo-600 dark:bg-indigo-500 border border-transparent rounded-md font-semibold text-xs text-white dark:text-indigo-100 hover:bg-indigo-500 dark:hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 mr-3">Edit</Link>
                                    <button v-if="can('roles.delete') && role.name !== 'admin' && role.name !== 'client'" @click="confirmDeleteRole(role.id)" class="inline-flex items-center px-3 py-1 bg-red-600 dark:bg-red-500 border border-transparent rounded-md font-semibold text-xs text-white dark:text-red-100 hover:bg-red-500 dark:hover:bg-red-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="allRoles.length === 0">
                                <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No roles found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <ConfirmModal :show="deleteModal.show" title="Confirm Delete" :message="deleteModal.message"
            @confirm="deleteModal.onConfirm" @update:show="deleteModal.show = $event" />
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { usePermission } from '../../../composables/usePermission';
import { useNotification } from '../../../composables/useNotification';
import { formatDate } from '../../../helpers/format';
import ConfirmModal from '../../../components/ConfirmModal.vue';
import Pagination from '../../../components/Pagination.vue';

const { can } = usePermission();
const { success: notifySuccess } = useNotification();

const props = defineProps({
    users: Object,
    allRoles: Array,
    filter: String,
});

const page = usePage();
const activeTab = ref(page.url.includes('tab=roles') ? 'roles' : 'users');
const roleFilter = ref(props.filter || '');

const deleteModal = reactive({
    show: false,
    message: '',
    onConfirm: () => {},
});

function confirmDeleteUser(id) {
    deleteModal.message = 'Are you sure you want to delete this user?';
    deleteModal.onConfirm = () => {
        router.delete(route('admin.users.destroy', { user: id }), {
            onSuccess: () => {
                notifySuccess('User deleted successfully.');
                router.reload({ only: ['users'] });
            },
        });
    };
    deleteModal.show = true;
}

function confirmDeleteRole(id) {
    deleteModal.message = 'Are you sure you want to delete this role?';
    deleteModal.onConfirm = () => {
        router.delete(route('admin.users.roles.destroy', { role: id }), {
            onSuccess: () => {
                notifySuccess('Role deleted successfully.');
                router.visit(route('admin.users', { tab: 'roles' }));
            },
        });
    };
    deleteModal.show = true;
}

function applyFilter() {
    router.visit(route('admin.users', { role: roleFilter.value || null }), {
        preserveState: true,
        replace: true,
    });
}

function tabClass(tab) {
    const base = 'border-b-2 transition-colors -mb-[1px]';
    return activeTab.value === tab
        ? `${base} border-indigo-600 dark:border-indigo-400 text-indigo-600 dark:text-indigo-400`
        : `${base} border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600`;
}
</script>
