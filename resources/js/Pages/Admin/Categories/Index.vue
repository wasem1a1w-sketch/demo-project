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
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="category in categories" :key="category.id">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ category.name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ category.slug }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ category.products_count }}</td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <button v-if="can('categories.update')" @click="editCategory(category)" class="inline-flex items-center px-3 py-1 bg-indigo-600 dark:bg-indigo-500 border border-transparent rounded-md font-semibold text-xs text-white mr-2 hover:bg-indigo-500">Edit</button>
                            <button v-if="can('categories.delete')" @click="deleteCategory(category.id)" class="inline-flex items-center px-3 py-1 bg-red-600 dark:bg-red-500 border border-transparent rounded-md font-semibold text-xs text-white hover:bg-red-500">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="categories.length === 0">
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No categories</td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>

        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Category"
            message="Are you sure you want to delete this category?"
            @confirm="confirmDelete"
            @cancel="showDeleteModal = false"
            @update:show="showDeleteModal = $event"
        />

        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center" @click.self="showEditModal = false">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg mx-4 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Edit Category</h3>
                    <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form @submit.prevent="updateCategory" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                        <input v-model="editForm.name" type="text" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Slug</label>
                        <input v-model="editForm.slug" type="text" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                        <input v-model="editForm.description" type="text" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="showEditModal = false" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { usePermission } from '../../../composables/usePermission';
import ConfirmModal from '../../../components/ConfirmModal.vue';

const { can } = usePermission();

defineProps({ categories: Array });

const showDeleteModal = ref(false);
const deletingCategoryId = ref(null);
const form = reactive({ name: '', slug: '', description: '' });

const showEditModal = ref(false);
const editForm = reactive({ id: null, name: '', slug: '', description: '' });

function createCategory() {
    router.post(route('admin.categories.store'), form, {
        onSuccess: () => {
            router.reload({ only: ['categories'] });
        },
    });
}

function editCategory(category) {
    editForm.id = category.id;
    editForm.name = category.name;
    editForm.slug = category.slug;
    editForm.description = category.description || '';
    showEditModal.value = true;
}

function updateCategory() {
    router.post(route('admin.categories.update', { id: editForm.id }), {
        name: editForm.name,
        slug: editForm.slug,
        description: editForm.description,
    }, {
        onSuccess: () => {
            showEditModal.value = false;
            router.reload({ only: ['categories'] });
        },
    });
}

function deleteCategory(id) {
    deletingCategoryId.value = id;
    showDeleteModal.value = true;
}

function confirmDelete() {
    showDeleteModal.value = false;
    router.delete(route('admin.categories.destroy', { id: deletingCategoryId.value }), {
        onSuccess: () => {
            router.reload({ only: ['categories'] });
        },
    });
}
</script>
