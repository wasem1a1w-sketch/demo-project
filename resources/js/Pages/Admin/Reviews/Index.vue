<template>
    <div class="px-4 py-6 sm:px-0">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Product Reviews</h1>

        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Rating</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Review</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="review in reviews.data" :key="review.id">
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                            <Link :href="route('product', review.product.slug)" class="hover:text-indigo-600">{{ review.product.name }}</Link>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ review.user?.name || 'Deleted User' }}</td>
                        <td class="px-6 py-4">
                            <StarRating :modelValue="review.rating" :readonly="true" :small="true" />
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 max-w-xs">
                            <p v-if="review.title" class="font-medium text-gray-900 dark:text-white truncate">{{ review.title }}</p>
                            <p v-if="review.body" class="truncate">{{ review.body }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <span v-if="review.is_approved" class="badge badge-success">Approved</span>
                            <span v-else class="badge bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300">Pending</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ review.created_at }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button @click="openModal(review)"
                                class="inline-flex items-center px-3 py-1 bg-indigo-600 dark:bg-indigo-500 border border-transparent rounded-md font-semibold text-xs text-white hover:bg-indigo-500 dark:hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 cursor-pointer">
                                View
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!reviews.data?.length">
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">No reviews yet.</td>
                    </tr>
                </tbody>
            </table>
            </div>
            <div v-if="reviews.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                <Pagination :links="reviews.links" />
            </div>
        </div>

        <!-- Review Detail Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="selectedReview" class="fixed inset-0 z-[70] flex items-center justify-center p-4" @click.self="closeModal">
                    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeModal"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto break-words">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Review Details</h3>
                            <button @click="closeModal" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Product</span>
                                <p class="text-sm font-medium text-gray-900 dark:text-white mt-0.5">
                                    <Link :href="route('product', selectedReview.product.slug)" class="hover:text-indigo-600">{{ selectedReview.product.name }}</Link>
                                </p>
                            </div>

                            <div>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">User</span>
                                <p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedReview.user?.name || 'Deleted User' }}</p>
                            </div>

                            <div>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Rating</span>
                                <div class="mt-1">
                                    <StarRating :modelValue="selectedReview.rating" :readonly="true" />
                                </div>
                            </div>

                            <div v-if="selectedReview.title">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Title</span>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ selectedReview.title }}</p>
                            </div>

                            <div v-if="selectedReview.body">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Review</span>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-0.5 whitespace-pre-line leading-relaxed">{{ selectedReview.body }}</p>
                            </div>

                            <div>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</span>
                                <div class="mt-1">
                                    <span v-if="selectedReview.is_approved" class="badge badge-success">Approved</span>
                                    <span v-else class="badge bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300">Pending</span>
                                </div>
                            </div>

                            <div>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Submitted</span>
                                <p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ selectedReview.created_at }}</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button v-if="!selectedReview.is_approved && can('reviews.update')"
                                @click="approve(selectedReview.id)"
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 cursor-pointer">
                                Approve
                            </button>
                            <button v-if="selectedReview.is_approved && can('reviews.update')"
                                @click="reject(selectedReview.id)"
                                class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white hover:bg-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150 cursor-pointer">
                                Reject
                            </button>
                            <button v-if="can('reviews.delete')"
                                @click="destroy(selectedReview.id)"
                                class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150 cursor-pointer">
                                Delete
                            </button>
                            <button @click="closeModal"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none transition ease-in-out duration-150 cursor-pointer">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Review"
            message="Are you sure you want to delete this review?"
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
import Pagination from '../../../components/Pagination.vue';
import StarRating from '../../../components/StarRating.vue';
import { useNotification } from '../../../composables/useNotification';

const props = defineProps({
    reviews: { type: Object, required: true },
});

const { can } = usePermission();
const selectedReview = ref(null);
const showDeleteModal = ref(false);
const { success } = useNotification();

function openModal(review) {
    selectedReview.value = review;
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    selectedReview.value = null;
    document.body.style.overflow = '';
}

function approve(id) {
    router.patch(route('admin.reviews.approve', id), {}, {
        onSuccess: () => { 
            selectedReview.value = null;
            success('Review approved successfully');
         },
    });
}

function reject(id) {
    router.patch(route('admin.reviews.reject', id), {}, {
        onSuccess: () => { 
            selectedReview.value = null;
            success('Review rejected');
         },
    });
}

function destroy(id) {
    showDeleteModal.value = true;
}

function confirmDelete() {
    showDeleteModal.value = false;
    router.delete(route('admin.reviews.destroy', selectedReview.value.id), {
        onSuccess: () => { 
            selectedReview.value = null;
            success('Review deleted');
        },
    });
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-active > div:last-child,
.modal-leave-active > div:last-child {
    transition: transform 0.25s ease, opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-from > div:last-child,
.modal-leave-to > div:last-child {
    transform: scale(0.95);
    opacity: 0;
}
</style>
