<template>
    <div class="px-4 py-6 sm:px-0">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Add Product</h1>

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
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Main Image</label>
                    <input type="file" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" @change="handleMainImage"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100">
                    <p class="mt-1 text-xs text-gray-500">Main product image shown on all pages. Max 3MB.</p>
                    <p v-if="mainImageError" class="mt-1 text-xs text-red-500">{{ mainImageError }}</p>
                    <div v-if="mainImagePreview" class="mt-4">
                        <div class="relative w-48 h-48 rounded-lg overflow-hidden border-2 border-indigo-500">
                            <img :src="mainImagePreview" class="w-full h-full object-contain">
                            <button type="button" @click="removeMainImage" class="absolute top-0 right-0 bg-red-500 text-white rounded-bl p-1 text-xs">&times;</button>
                            <div class="absolute bottom-0 left-0 right-0 bg-indigo-600 text-white text-xs py-1 text-center font-medium">Main Image</div>
                        </div>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gallery Images</label>
                    <input type="file" multiple accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" @change="handleGalleryImages"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100">
                    <p class="mt-1 text-xs text-gray-500">Up to 4 additional images, 3MB each (jpeg, png, jpg, gif, webp).</p>
                    <p v-if="galleryError" class="mt-1 text-xs text-red-500">{{ galleryError }}</p>
                    <div v-if="galleryPreviews.length" class="mt-4 flex gap-2 flex-wrap">
                        <div v-for="(img, idx) in galleryPreviews" :key="idx"
                             class="relative w-20 h-20 rounded-lg overflow-hidden border border-gray-300 dark:border-gray-600">
                            <img :src="img" class="w-full h-full object-contain">
                            <button type="button" @click="removeGalleryImage(idx)" class="absolute top-0 right-0 bg-red-500 text-white rounded-bl p-0.5 text-xs leading-none">&times;</button>
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
            <div v-if="can('products.create')" class="mt-6 flex gap-4">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700">
                    Save Product
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
import { usePermission } from '../../../composables/usePermission';

const { can } = usePermission();

defineProps({ categories: Array });

const form = reactive({
    name: '', slug: '', price: '', compare_price: '', stock: 0,
    category_id: '', sku: '', weight: '', short_description: '',
    description: '', is_active: true, is_featured: false,
});

const mainImage = ref(null);
const mainImagePreview = ref('');
const mainImageError = ref('');
const galleryImages = ref([]);
const galleryPreviews = ref([]);
const galleryError = ref('');

function handleMainImage(e) {
    const file = e.target.files[0];
    mainImageError.value = '';
    if (!file) return;
    if (file.size > 3 * 1024 * 1024) {
        mainImageError.value = `"${file.name}" exceeds the 3MB limit.`;
        e.target.value = '';
        return;
    }
    mainImage.value = file;
    const reader = new FileReader();
    reader.onload = (e) => mainImagePreview.value = e.target.result;
    reader.readAsDataURL(file);
    e.target.value = '';
}

function removeMainImage() {
    mainImage.value = null;
    mainImagePreview.value = '';
}

function handleGalleryImages(e) {
    const files = Array.from(e.target.files);
    galleryError.value = '';
    for (const file of files) {
        if (file.size > 3 * 1024 * 1024) {
            galleryError.value = `"${file.name}" exceeds the 3MB limit.`;
            continue;
        }
        if (galleryImages.value.length >= 4) {
            galleryError.value = 'Maximum 4 gallery images allowed.';
            break;
        }
        galleryImages.value.push(file);
        const reader = new FileReader();
        reader.onload = (e) => galleryPreviews.value.push(e.target.result);
        reader.readAsDataURL(file);
    }
    e.target.value = '';
}

function removeGalleryImage(idx) {
    galleryImages.value.splice(idx, 1);
    galleryPreviews.value.splice(idx, 1);
}

function submit() {
    const data = new FormData();
    Object.keys(form).forEach(key => {
        if (form[key] !== null && form[key] !== '') {
            data.append(key, form[key]);
        }
    });
    if (mainImage.value) {
        data.append('main_image', mainImage.value);
    }
    galleryImages.value.forEach(img => {
        data.append('gallery_images[]', img);
    });
    router.post(route('admin.products.store'), data, {
        forceFormData: true,
        onSuccess: () => { router.visit(route('admin.products')); },
        onError: (errors) => { console.error('Store failed:', errors); }
    });
}
</script>
