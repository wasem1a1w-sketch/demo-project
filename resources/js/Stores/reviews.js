import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const useReviewStore = defineStore('reviews', () => {
    const reviews = ref([]);
    const loading = ref(false);

    async function fetchReviews(productId) {
        loading.value = true;
        try {
            const response = await axios.get(`/api/products/${productId}/reviews`);
            reviews.value = response.data.data || [];
        } catch {
            reviews.value = [];
        } finally {
            loading.value = false;
        }
    }

    async function submitReview(productId, data) {
        const response = await axios.post(`/api/products/${productId}/reviews`, data);
        return response.data;
    }

    async function updateReview(productId, reviewId, data) {
        const response = await axios.put(`/api/products/${productId}/reviews/${reviewId}`, data);
        return response.data;
    }

    async function deleteReview(productId, reviewId) {
        await axios.delete(`/api/products/${productId}/reviews/${reviewId}`);
    }

    return {
        reviews,
        loading,
        fetchReviews,
        submitReview,
        updateReview,
        deleteReview,
    };
});
