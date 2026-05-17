<template>
    <div class="px-4 py-6 sm:px-0">
        <div class="mb-6">
            <div class="flex items-center gap-3">
                <Link :href="route('admin.products')" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ product.name }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">SKU: {{ product.sku || '—' }} · Slug: {{ product.slug }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Description</h3>
                    <p class="text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ product.description || 'No description' }}</p>
                </div>

                <div v-if="product.images?.length" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Images</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        <div v-for="image in product.images" :key="image.id" class="relative group">
                            <img :src="`/${image.image_path}`" class="w-full h-32 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                            <span v-if="image.is_primary" class="absolute top-2 left-2 bg-indigo-600 text-white text-xs px-2 py-0.5 rounded font-medium">Primary</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Details</h3>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Price</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">${{ Number(product.price).toFixed(2) }}</dd>
                        </div>
                        <div v-if="product.compare_price" class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Compare Price</dt>
                            <dd class="font-medium text-gray-500 line-through">${{ Number(product.compare_price).toFixed(2) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Stock</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ product.stock }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Category</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ product.category?.name || '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                            <dd>
                                <span v-if="product.is_active" class="text-green-600 dark:text-green-400 font-medium">Active</span>
                                <span v-else class="text-red-600 dark:text-red-400 font-medium">Inactive</span>
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Featured</dt>
                            <dd>
                                <span v-if="product.is_featured" class="text-indigo-600 dark:text-indigo-400 font-medium">Yes</span>
                                <span v-else class="text-gray-400">No</span>
                            </dd>
                        </div>
                        <div v-if="product.weight" class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Weight</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ product.weight }}{{ product.weight_unit || 'g' }}</dd>
                        </div>
                    </dl>
                </div>

                <div v-if="can('products.update')" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                    <Link :href="route('admin.products.edit', { id: product.id })" class="inline-flex items-center justify-center w-full gap-2 bg-indigo-600 text-white px-4 py-2.5 rounded-lg hover:bg-indigo-700 transition-colors font-medium text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Edit Product
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { usePermission } from '../../../composables/usePermission';

const { can } = usePermission();

defineProps({ product: Object });
</script>
