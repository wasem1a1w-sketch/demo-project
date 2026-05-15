<template>
    <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Customer Reviews</h3>

        <div v-if="reviewsCount" class="flex items-center gap-3 mb-6">
            <StarRating :modelValue="Math.round(Number(avgRating))" :readonly="true" />
            <span class="text-sm text-gray-500 dark:text-gray-400">
                {{ Number(avgRating).toFixed(1) }} out of 5 — {{ reviewsCount }} review{{ reviewsCount !== 1 ? 's' : '' }}
            </span>
        </div>

        <div v-if="loading" class="text-center py-4">
            <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"></div>
        </div>

        <template v-else-if="user">
            <div v-if="myReview && !editing" class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6 mb-6">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-semibold text-gray-900 dark:text-white">Your Review</h4>
                    <div class="flex items-center gap-2">
                        <span v-if="!myReview.is_approved" class="text-xs text-yellow-600 dark:text-yellow-400 font-medium bg-yellow-50 dark:bg-yellow-900/20 px-2 py-0.5 rounded">Pending Approval</span>
                        <button @click="startEditing" class="inline-flex items-center px-2.5 py-1 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">Edit</button>
                        <button @click="handleDelete" class="inline-flex items-center px-2.5 py-1 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">Delete</button>
                    </div>
                </div>
                <StarRating :modelValue="myReview.rating" :readonly="true" />
                <p v-if="myReview.title" class="font-semibold text-gray-900 dark:text-white mt-2">{{ myReview.title }}</p>
                <p v-if="myReview.body" class="text-gray-600 dark:text-gray-300 text-sm mt-1">{{ myReview.body }}</p>
            </div>

            <form v-else @submit.prevent="editing ? handleUpdate() : handleSubmit()" class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6 mb-6">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-3">{{ editing ? 'Edit Your Review' : 'Write a Review' }}</h4>

                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rating</label>
                    <StarRating v-model="form.rating" />
                </div>

                <div class="mb-3">
                    <label for="review-title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                    <input
                        id="review-title"
                        v-model="form.title"
                        type="text"
                        placeholder="Summarize your experience"
                        class="input w-full"
                    />
                </div>

                <div class="mb-4">
                    <label for="review-body" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Review</label>
                    <textarea
                        id="review-body"
                        v-model="form.body"
                        rows="3"
                        placeholder="Tell others about your experience"
                        class="input w-full"
                    ></textarea>
                </div>

                <div v-if="error" class="text-red-500 dark:text-red-400 text-sm mb-3">{{ error }}</div>

                <div class="flex items-center gap-2">
                    <button type="submit" :disabled="submitting || !form.rating"
                        class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-indigo-700 disabled:opacity-50">
                        {{ submitting ? 'Saving...' : (editing ? 'Update Review' : 'Submit Review') }}
                    </button>
                    <button v-if="editing" type="button" @click="cancelEditing"
                        class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                        Cancel
                    </button>
                </div>
            </form>
        </template>

        <div v-else class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6 text-center mb-6">
            <p class="text-gray-600 dark:text-gray-400">
                <Link :href="route('login')" class="text-indigo-600 dark:text-indigo-400 hover:underline">Log in</Link>
                to leave a review.
            </p>
        </div>

        <div v-if="otherReviews.length === 0 && !loading" class="text-gray-500 dark:text-gray-400 text-center py-6">
            No reviews yet.
        </div>

        <div v-for="review in otherReviews" :key="review.id" class="border-b border-gray-200 dark:border-gray-700 py-4 last:border-0">
            <div class="flex items-center gap-2 mb-1">
                <StarRating :modelValue="review.rating" :readonly="true" />
            </div>
            <p v-if="review.title" class="font-semibold text-gray-900 dark:text-white">{{ review.title }}</p>
            <p v-if="review.body" class="text-gray-600 dark:text-gray-300 text-sm mt-1">{{ review.body }}</p>
            <p class="text-xs text-gray-400 mt-1">by {{ review.user?.name || 'Anonymous' }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { useReviewStore } from '../Stores/reviews';
import StarRating from './StarRating.vue';

const props = defineProps({
    productId: { type: Number, required: true },
    avgRating: { type: Number, default: 0 },
    reviewsCount: { type: Number, default: 0 },
});

const page = usePage();
const reviewStore = useReviewStore();
const user = computed(() => page.props.auth?.user);

const loading = ref(true);
const editing = ref(false);
const form = ref({ rating: 0, title: '', body: '' });
const submitting = ref(false);
const error = ref('');
const reviewList = ref([]);

const myReview = computed(() => {
    if (!user.value) return null;
    return reviewList.value.find(r => r.user_id === user.value.id) || null;
});

const otherReviews = computed(() => {
    if (!user.value) return reviewList.value;
    return reviewList.value.filter(r => r.user_id !== user.value.id);
});

onMounted(() => {
    loadReviews();
});

async function loadReviews() {
    loading.value = true;
    try {
        const response = await axios.get(`/api/products/${props.productId}/reviews`);
        reviewList.value = response.data.data || [];
    } catch (err) {
        console.error('Failed to load reviews:', err.response?.status, err.message);
        reviewList.value = [];
    } finally {
        loading.value = false;
    }
}

function startEditing() {
    form.value = {
        rating: myReview.value.rating,
        title: myReview.value.title || '',
        body: myReview.value.body || '',
    };
    editing.value = true;
}

function cancelEditing() {
    editing.value = false;
    form.value = { rating: 0, title: '', body: '' };
    error.value = '';
}

async function handleSubmit() {
    submitting.value = true;
    error.value = '';
    try {
        const review = await reviewStore.submitReview(props.productId, {
            rating: form.value.rating,
            title: form.value.title,
            body: form.value.body,
        });
        reviewList.value.unshift(review);
        form.value = { rating: 0, title: '', body: '' };
    } catch (err) {
        console.error('Review submit error:', err.response?.status, err.response?.data, err.message);
        error.value = err.response?.data?.message || 'Failed to submit review.';
    } finally {
        submitting.value = false;
    }
}

async function handleUpdate() {
    submitting.value = true;
    error.value = '';
    try {
        const review = await reviewStore.updateReview(props.productId, myReview.value.id, {
            rating: form.value.rating,
            title: form.value.title,
            body: form.value.body,
        });
        const index = reviewList.value.findIndex(r => r.id === review.id);
        if (index !== -1) {
            reviewList.value[index] = review;
        }
        editing.value = false;
        form.value = { rating: 0, title: '', body: '' };
    } catch (err) {
        console.error('Review update error:', err.response?.status, err.response?.data, err.message);
        error.value = err.response?.data?.message || 'Failed to update review.';
    } finally {
        submitting.value = false;
    }
}

async function handleDelete() {
    if (!confirm('Delete your review?')) return;
    try {
        await reviewStore.deleteReview(props.productId, myReview.value.id);
        reviewList.value = reviewList.value.filter(r => r.id !== myReview.value.id);
    } catch (err) {
        console.error('Review delete error:', err);
    }
}
</script>
