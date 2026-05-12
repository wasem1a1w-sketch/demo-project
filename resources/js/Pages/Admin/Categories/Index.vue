<template>
    <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Categories</h1>
        </div>

        <div v-if="can('categories.create')" class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg mb-6 p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Add Category</h3>
            <form @submit.prevent="createCategory" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input v-model="form.name" type="text" placeholder="Name" required class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500">
                <input v-model="form.slug" type="text" placeholder="Slug" required class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500">
                <input v-model="form.description" type="text" placeholder="Description" class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500">
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Add</button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Slug</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Products</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="category in categories" :key="category.id">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ category.name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ category.slug }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ category.products_count }}</td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <button v-if="can('categories.delete')" @click="deleteCategory(category.id)" class="inline-flex items-center px-3 py-1 bg-red-600 dark:bg-red-500 border border-transparent rounded-md font-semibold text-xs text-white dark:text-red-100 hover:bg-red-500 dark:hover:bg-red-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="categories.length === 0">
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No categories</td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { usePermission } from '../../../composables/usePermission';

const { can } = usePermission();

defineProps({ categories: Array });

const form = reactive({ name: '', slug: '', description: '' });

function createCategory() {
    router.post(route('admin.categories.store'), form, {
        onSuccess: () => {
            router.reload({ only: ['categories'] });
        },
    });
}

function deleteCategory(id) {
    if (confirm('Delete this category?')) {
        router.delete(route('admin.categories.destroy', { id }), {
            onSuccess: () => {
                router.reload({ only: ['categories'] });
            },
        });
    }
}
</script>