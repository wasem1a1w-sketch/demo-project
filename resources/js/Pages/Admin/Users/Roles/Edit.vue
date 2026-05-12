<template>
    <div class="px-4 py-6 sm:px-0">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Edit Role</h1>

        <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="max-w-md">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role Name *</label>
                    <input v-model="form.name" type="text" required
                        class="mt-1.5 block w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
            </div>

            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Permissions</h3>
                    <div class="flex gap-2">
                        <button type="button" @click="selectAll" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">Select All</button>
                        <button type="button" @click="deselectAll" class="text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">Deselect All</button>
                    </div>
                </div>

                <div v-for="(perms, group) in groupedPermissions" :key="group" class="mb-8 last:mb-0">
                    <div class="flex items-center gap-3 mb-3 px-1">
                        <label class="flex items-center gap-2.5 cursor-pointer select-none py-0.5">
                            <div class="w-5 h-5 rounded flex items-center justify-center shrink-0 transition-all duration-150"
                                :class="checkState(perms) === 'all'
                                ? 'bg-emerald-600 text-white'
                                : checkState(perms) === 'some'
                                    ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 border-2 border-emerald-300 dark:border-emerald-600'
                                    : 'bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-500'">
                                <svg v-if="checkState(perms) === 'all'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <svg v-else-if="checkState(perms) === 'some'" class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M5 12h14"></path>
                                </svg>
                            </div>
                            <input type="checkbox" class="sr-only" :checked="checkState(perms) === 'all'" @change="toggleGroup(perms, $event.target.checked)">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ group }}</span>
                        </label>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ selectedCount(perms) }}/{{ perms.length }}</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2">
                        <label v-for="perm in perms" :key="perm.id"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg border cursor-pointer transition-all duration-150"
                            :class="isSelected(perm.name)
                                ? 'border-emerald-500 bg-emerald-50/70 dark:bg-emerald-900/20 ring-1 ring-emerald-500'
                                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700/30'">
                            <div class="w-5 h-5 rounded flex items-center justify-center shrink-0 transition-all duration-150"
                                :class="isSelected(perm.name)
                                    ? 'bg-emerald-600 text-white'
                                    : 'bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-500'">
                                <svg v-if="isSelected(perm.name)" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <input type="checkbox" :value="perm.name" v-model="form.permissions" class="sr-only">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200 leading-tight">{{ permLabel(perm) }}</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700 flex gap-3">
                <button type="submit" class="bg-indigo-600 text-white px-5 py-2 rounded-lg hover:bg-indigo-700 font-medium text-sm transition">
                    Update Role
                </button>
                <Link :href="route('admin.users')" class="text-gray-700 dark:text-gray-300 px-5 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 font-medium text-sm transition">
                    Cancel
                </Link>
            </div>
        </form>
    </div>
</template>

<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useNotification } from '../../../../composables/useNotification';

const { success: notifySuccess, error: notifyError } = useNotification();

const props = defineProps({
    role: Object,
    rolePermissions: Array,
    groupedPermissions: Object,
});

const form = reactive({
    name: props.role.name,
    permissions: [...props.rolePermissions],
});

function permLabel(perm) {
    const parts = perm.name.split('.');
    return parts.slice(1).map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
}

function isSelected(name) {
    return form.permissions.includes(name);
}

function checkState(perms) {
    const selected = perms.filter(p => form.permissions.includes(p.name)).length;
    if (selected === 0) return 'none';
    if (selected === perms.length) return 'all';
    return 'some';
}

function selectedCount(perms) {
    return perms.filter(p => form.permissions.includes(p.name)).length;
}

function toggleGroup(perms, checked) {
    const names = perms.map(p => p.name);
    if (checked) {
        for (const name of names) {
            if (!form.permissions.includes(name)) {
                form.permissions.push(name);
            }
        }
    } else {
        form.permissions = form.permissions.filter(p => !names.includes(p));
    }
}

function selectAll() {
    const all = [];
    for (const perms of Object.values(props.groupedPermissions)) {
        for (const perm of perms) {
            all.push(perm.name);
        }
    }
    form.permissions = all;
}

function deselectAll() {
    form.permissions = [];
}

function submit() {
    router.post(route('admin.users.roles.update', { role: props.role.id }), form, {
        onSuccess: () => {
            notifySuccess('Role updated successfully.');
            router.visit(route('admin.users', { tab: 'roles' }));
        },
        onError: (errors) => notifyError(Object.values(errors)[0] || 'Validation failed.'),
    });
}
</script>
