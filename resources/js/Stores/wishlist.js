import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useWishlistStore = defineStore('wishlist', () => {
    const items = ref([]);
    const loading = ref(false);

    const itemCount = computed(() => items.value.length);

    const wishlistedIds = computed(() => new Set(items.value.map(i => i.product_id)));

    function isWishlisted(productId) {
        return wishlistedIds.value.has(productId);
    }

    async function fetchWishlist() {
        try {
            const response = await axios.get('/api/wishlist');
            items.value = response.data.items || [];
        } catch (error) {
            items.value = [];
        }
    }

    async function toggleItem(productId) {
        const existing = items.value.find(i => i.product_id === productId);
        if (existing) {
            await removeItem(existing.id);
        } else {
            await addItem(productId);
        }
    }

    async function addItem(productId) {
        try {
            const response = await axios.post('/api/wishlist/add', { product_id: productId });
            items.value = response.data.items || [];
        } catch (error) {
            console.error('Failed to add wishlist item:', error);
        }
    }

    async function removeItem(id) {
        try {
            const response = await axios.delete(`/api/wishlist/${id}`);
            items.value = response.data.items || [];
        } catch (error) {
            console.error('Failed to remove wishlist item:', error);
        }
    }

    async function clearWishlist() {
        try {
            await axios.delete('/api/wishlist');
            items.value = [];
        } catch (error) {
            console.error('Failed to clear wishlist:', error);
        }
    }

    return {
        items,
        loading,
        itemCount,
        wishlistedIds,
        isWishlisted,
        fetchWishlist,
        toggleItem,
        addItem,
        removeItem,
        clearWishlist,
    };
});
