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
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current Main Image</label>
                    <div v-if="currentMainImage" class="mb-4">
                        <div class="relative w-48 h-48 rounded-lg overflow-hidden border-2 border-indigo-500">
                            <img :src="`/${currentMainImage.image_path}`" class="w-full h-full object-contain">
                            <button v-if="can('products.images.delete')" type="button" @click="deleteImage(currentMainImage.id)" class="absolute top-0 right-0 bg-red-500 text-white rounded-bl p-1 text-xs">&times;</button>
                            <div class="absolute bottom-0 left-0 right-0 bg-indigo-600 text-white text-xs py-1 text-center font-medium">Main Image</div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-500 dark:text-gray-400 mb-4">No main image uploaded.</p>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Replace Main Image</label>
                    <input type="file" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" @change="handleMainImage"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100">
                    <p class="mt-1 text-xs text-gray-500">Max 3MB. Leave empty to keep current main image.</p>
                    <p v-if="mainImageError" class="mt-1 text-xs text-red-500">{{ mainImageError }}</p>
                    <div v-if="newMainImagePreview" class="mt-4">
                        <div class="relative w-48 h-48 rounded-lg overflow-hidden border-2 border-indigo-500">
                            <img :src="newMainImagePreview" class="w-full h-full object-contain">
                            <button type="button" @click="removeNewMainImage" class="absolute top-0 right-0 bg-red-500 text-white rounded-bl p-1 text-xs">&times;</button>
                            <div class="absolute bottom-0 left-0 right-0 bg-indigo-600 text-white text-xs py-1 text-center font-medium">New Main Image</div>
                        </div>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current Gallery Images</label>
                    <div v-if="currentGalleryImages.length" class="flex gap-2 flex-wrap mb-4">
                        <div v-for="img in currentGalleryImages" :key="img.id"
                             class="relative w-20 h-20 rounded-lg overflow-hidden border border-gray-300 dark:border-gray-600">
                            <img :src="`/${img.image_path}`" class="w-full h-full object-contain">
                            <button v-if="can('products.images.delete')" type="button" @click="deleteImage(img.id)" class="absolute top-0 right-0 bg-red-500 text-white rounded-bl p-0.5 text-xs leading-none">&times;</button>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-500 dark:text-gray-400 mb-4">No gallery images.</p>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Add Gallery Images</label>
                    <input type="file" multiple accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" @change="handleGalleryImages"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100">
                    <p class="mt-1 text-xs text-gray-500">Up to 4 gallery images total (including existing), 3MB each.</p>
                    <p v-if="galleryError" class="mt-1 text-xs text-red-500">{{ galleryError }}</p>
                    <div v-if="newGalleryPreviews.length" class="mt-4 flex gap-2 flex-wrap">
                        <div v-for="(img, idx) in newGalleryPreviews" :key="'new-'+idx"
                             class="relative w-20 h-20 rounded-lg overflow-hidden border border-gray-300 dark:border-gray-600">
                            <img :src="img" class="w-full h-full object-contain">
                            <button type="button" @click="removeNewGalleryImage(idx)" class="absolute top-0 right-0 bg-red-500 text-white rounded-bl p-0.5 text-xs leading-none">&times;</button>
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
            <div v-if="can('products.update')" class="mt-6 flex gap-4">
                <button type="submit" :disabled="submitting" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                    {{ submitting ? 'Updating...' : 'Update Product' }}
                </button>
                <Link :href="route('admin.products')" class="text-gray-700 dark:text-gray-300 px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                    Cancel
                </Link>
            </div>
        </form>

        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Image"
            message="Are you sure you want to delete this image?"
            @confirm="confirmDeleteImage"
            @cancel="showDeleteModal = false"
            @update:show="showDeleteModal = $event"
        />
    </div>
</template>

<script setup>
import { reactive, ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useNotification } from '../../../composables/useNotification';
import { usePermission } from '../../../composables/usePermission';
import ConfirmModal from '../../../components/ConfirmModal.vue';

const { can } = usePermission();
const { success } = useNotification();

const props = defineProps({ product: Object, categories: Array });

const form = reactive({ ...props.product });
const submitting = ref(false);
const showDeleteModal = ref(false);
const deletingImageId = ref(null);

const currentMainImage = computed(() => props.product.images?.find(i => i.is_primary) || null);
const currentGalleryImages = computed(() => props.product.images?.filter(i => !i.is_primary) || []);

const newMainImage = ref(null);
const newMainImagePreview = ref('');
const mainImageError = ref('');
const newGalleryImages = ref([]);
const newGalleryPreviews = ref([]);
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
    newMainImage.value = file;
    const reader = new FileReader();
    reader.onload = (e) => newMainImagePreview.value = e.target.result;
    reader.readAsDataURL(file);
    e.target.value = '';
}

function removeNewMainImage() {
    newMainImage.value = null;
    newMainImagePreview.value = '';
}

function handleGalleryImages(e) {
    const files = Array.from(e.target.files);
    galleryError.value = '';
    for (const file of files) {
        if (file.size > 3 * 1024 * 1024) {
            galleryError.value = `"${file.name}" exceeds the 3MB limit.`;
            continue;
        }
        if (newGalleryImages.value.length >= 4) {
            galleryError.value = 'Maximum 4 gallery images allowed.';
            break;
        }
        newGalleryImages.value.push(file);
        const reader = new FileReader();
        reader.onload = (e) => newGalleryPreviews.value.push(e.target.result);
        reader.readAsDataURL(file);
    }
    e.target.value = '';
}

function removeNewGalleryImage(idx) {
    newGalleryImages.value.splice(idx, 1);
    newGalleryPreviews.value.splice(idx, 1);
}

function deleteImage(imageId) {
    deletingImageId.value = imageId;
    showDeleteModal.value = true;
}

function confirmDeleteImage() {
    showDeleteModal.value = false;
    router.delete(route('admin.product-images.destroy', { id: deletingImageId.value }), {
        onSuccess: () => {
            props.product.images = props.product.images.filter(i => i.id !== deletingImageId.value);
        },
    });
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

    if (newMainImage.value) {
        data.append('main_image', newMainImage.value);
    }

    newGalleryImages.value.forEach(img => {
        data.append('gallery_images[]', img);
    });

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
