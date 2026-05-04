<template>
    <div class="px-4 py-6 sm:px-0">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Edit Product</h1>

        <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name *</label>
                    <input v-model="form.name" type="text" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Slug *</label>
                    <input v-model="form.slug" type="text" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Price *</label>
                    <input v-model="form.price" type="number" step="0.01" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Compare Price</label>
                    <input v-model="form.compare_price" type="number" step="0.01" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stock *</label>
                    <input v-model="form.stock" type="number" required class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                    <select v-model="form.category_id" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">Select category</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">SKU</label>
                    <input v-model="form.sku" type="text" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Weight</label>
                    <input v-model="form.weight" type="number" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Short Description</label>
                    <textarea v-model="form.short_description" rows="2" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <textarea v-model="form.description" rows="4" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current Images</label>
                    <div v-if="product.images?.length" class="flex gap-2 flex-wrap">
                        <div v-for="(img, idx) in product.images" :key="img.id"
                             :class="product.primary_image_id === img.id ? 'ring-2 ring-indigo-600 rounded-lg' : ''"
                             class="relative w-20 h-20 rounded-lg overflow-hidden border border-gray-300 dark:border-gray-600">
                            <img :src="`/${img.image_path}`" class="w-full h-full object-contain">
                            <button type="button" @click="deleteImage(img.id)" class="absolute top-0 right-0 bg-red-500 text-white rounded-bl p-0.5 text-xs leading-none">&times;</button>
                            <div class="absolute bottom-0 left-0 right-0 bg-black/50 text-white text-xs py-0.5 text-center">
                                {{ product.primary_image_id === img.id ? 'Primary' : '' }}
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-500 dark:text-gray-400">No images uploaded yet.</p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Add New Images</label>
                    <input type="file" multiple accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" @change="handleImageSelect"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100">
                    <p class="mt-1 text-xs text-gray-500">Max 10 images, 2MB each (jpeg, png, jpg, gif, webp)</p>

                    <div v-if="newImagePreviews.length" class="mt-4 flex gap-2 flex-wrap">
                        <div v-for="(img, idx) in newImagePreviews" :key="'new-'+idx"
                             class="relative w-20 h-20 rounded-lg overflow-hidden border border-gray-300 dark:border-gray-600">
                            <img :src="img" class="w-full h-full object-contain">
                            <button type="button" @click="removeNewImage(idx)" class="absolute top-0 right-0 bg-red-500 text-white rounded-bl p-0.5 text-xs leading-none">&times;</button>
                        </div>
                    </div>
                </div>
                <div class="md:col-span-2 flex gap-6">
                    <label class="flex items-center text-gray-700 dark:text-gray-300">
                        <input v-model="form.is_active" type="checkbox" class="mr-2">
                        Active
                    </label>
                    <label class="flex items-center text-gray-700 dark:text-gray-300">
                        <input v-model="form.is_featured" type="checkbox" class="mr-2">
                        Featured
                    </label>
                </div>
            </div>
            <div class="mt-6 flex gap-4">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700">
                    Update Product
                </button>
                <Link :href="route('admin.products')" class="text-gray-700 dark:text-gray-300 px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                    Cancel
                </Link>
            </div>
        </form>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useNotification } from '../../../composables/useNotification';
import axios from 'axios';

const { success } = useNotification();

const props = defineProps({ product: Object, categories: Array });

const form = reactive({ ...props.product });
const newImages = ref([]);
const newImagePreviews = ref([]);
const submitting = ref(false);

function handleImageSelect(e) {
    const files = Array.from(e.target.files);
    files.forEach(file => {
        newImages.value.push(file);
        const reader = new FileReader();
        reader.onload = (e) => newImagePreviews.value.push(e.target.result);
        reader.readAsDataURL(file);
    });
    e.target.value = '';
}

function removeNewImage(idx) {
    newImages.value.splice(idx, 1);
    newImagePreviews.value.splice(idx, 1);
}

function deleteImage(imageId) {
    if (confirm('Delete this image?')) {
        router.delete(route('admin.product-images.destroy', { id: imageId }), {
            onSuccess: () => {
                props.product.images = props.product.images.filter(i => i.id !== imageId);
            },
        });
    }
}

function submit() {
    submitting.value = true;
    const data = new FormData();

    const formFields = ['name', 'slug', 'description', 'short_description', 'price', 'compare_price', 'stock', 'category_id', 'is_active', 'is_featured', 'sku', 'weight', 'weight_unit'];
    formFields.forEach(key => {
        if (form[key] !== null && form[key] !== undefined) {
            data.append(key, form[key]);
        }
    });

    if (newImages.value.length > 0) {
        newImages.value.forEach((img, idx) => {
            data.append('new_images[]', img);
        });
    }

    router.post(route('admin.products.update', { id: props.product.id }), data, {
        forceFormData: true,
        onSuccess: () => {
            success('Product updated successfully!');
            router.visit(route('admin.products'));
        },
        onError: (errors) => {
            console.error('Update failed:', errors);
        },
        onFinish: () => {
            submitting.value = false;
        }
    });
}
</script>