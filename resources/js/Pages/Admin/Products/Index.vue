<template>
    <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Products</h1>
            <Link v-if="can('products.create')" :href="route('admin.products.create')" class="btn-primary">
                Add Product
            </Link>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Image</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="product in products.data" :key="product.id">
                        <td class="px-6 py-4">
                            <img v-if="product.images?.length" :src="`/${product.images[0].image_path}`" class="w-12 h-12 rounded object-contain">
                            <div v-else class="w-12 h-12 rounded bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ product.name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ product.sku }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ product.category?.name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            ${{ product.price }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ product.stock }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span v-if="product.is_active" class="badge badge-success">
                                Active
                            </span>
                            <span v-else class="badge bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                Inactive
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <Link v-if="can('products.update')" :href="route('admin.products.edit', { id: product.id })" class="inline-flex items-center px-3 py-1 bg-indigo-600 dark:bg-indigo-500 border border-transparent rounded-md font-semibold text-xs text-white dark:text-indigo-100 hover:bg-indigo-500 dark:hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 mr-3">Edit</Link>
                            <button v-if="can('products.delete')" @click="deleteProduct(product.id)" class="inline-flex items-center px-3 py-1 bg-red-600 dark:bg-red-500 border border-transparent rounded-md font-semibold text-xs text-white dark:text-red-100 hover:bg-red-500 dark:hover:bg-red-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="products.data.length === 0">
                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No products yet</td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>

        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Product"
            message="Are you sure you want to delete this product?"
            @confirm="confirmDelete"
            @cancel="showDeleteModal = false"
            @update:show="showDeleteModal = $event"
        />
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { usePermission } from '../../../composables/usePermission';
import ConfirmModal from '../../../components/ConfirmModal.vue';

const { can } = usePermission();
const showDeleteModal = ref(false);
const deletingProductId = ref(null);

defineProps({
    products: Object,
    categories: Array,
});

function deleteProduct(id) {
    deletingProductId.value = id;
    showDeleteModal.value = true;
}

function confirmDelete() {
    showDeleteModal.value = false;
    router.delete(route('admin.products.destroy', { id: deletingProductId.value }), {
        onSuccess: () => {
            router.reload({ only: ['products'] });
        },
    });
}
</script>