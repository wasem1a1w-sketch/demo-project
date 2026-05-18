import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useCartStore = defineStore('cart', () => {
    const items = ref([]);
    const loading = ref(false);
    const coupon = ref(null);
    const settings = ref({
        shipping_rate: 15,
        free_shipping_threshold: 100,
        tax_rate: 10,
    });

    const itemCount = computed(() => {
        return items.value.reduce((sum, item) => sum + item.quantity, 0);
    });

    const subtotal = computed(() => {
        return items.value.reduce((sum, item) => sum + (Number(item.product?.price || 0) * item.quantity), 0);
    });

    const discount = computed(() => {
        if (!coupon.value) return 0;
        
        let discountAmount = coupon.value.type === 'percentage'
            ? subtotal.value * (coupon.value.value / 100)
            : coupon.value.value;

        if (coupon.value.max_discount_amount) {
            discountAmount = Math.min(discountAmount, coupon.value.max_discount_amount);
        }
        
        return Math.min(discountAmount, subtotal.value);
    });

    const tax = computed(() => {
        const rate = Number(settings.value.tax_rate) / 100;
        return (subtotal.value - discount.value) * rate;
    });

    const shipping = computed(() => {
        const threshold = Number(settings.value.free_shipping_threshold);
        const rate = Number(settings.value.shipping_rate);
        return subtotal.value - discount.value >= threshold ? 0 : rate;
    });

    const total = computed(() => {
        return subtotal.value - discount.value + tax.value + shipping.value;
    });

    async function fetchSettings() {
        try {
            const response = await axios.get('/api/settings');
            if (Object.keys(response.data).length > 0) {
                settings.value = response.data;
            }
        } catch (error) {
            console.error('Failed to fetch settings:', error);
        }
    }

    async function fetchCart() {
        loading.value = true;
        try {
            const response = await axios.get('/api/cart');
            items.value = response.data.items;
            coupon.value = response.data.coupon;
        } catch (error) {
            console.error('Failed to fetch cart:', error);
        } finally {
            loading.value = false;
        }
    }

    async function addItem(productId, quantity = 1) {
        loading.value = true;
        try {
            const response = await axios.post('/api/cart/add', {
                product_id: productId,
                quantity: quantity,
            });
            items.value = response.data.items;
            coupon.value = response.data.coupon;
        } catch (error) {
            console.error('Failed to add item:', error);
            throw error;
        } finally {
            loading.value = false;
        }
    }

    async function updateItem(itemId, quantity) {
        loading.value = true;
        try {
            const response = await axios.patch(`/api/cart/${itemId}`, {
                quantity: quantity
            });
            items.value = response.data.items;
        } catch (error) {
            console.error('Failed to update item:', error);
            throw error;
        } finally {
            loading.value = false;
        }
    }

    async function removeItem(itemId) {
        loading.value = true;
        try {
            const response = await axios.delete(`/api/cart/${itemId}`);
            items.value = response.data.items;
        } catch (error) {
            console.error('Failed to remove item:', error);
            throw error;
        } finally {
            loading.value = false;
        }
    }

    async function applyCoupon(code) {
        loading.value = true;
        try {
            const response = await axios.post('/api/cart/coupon', { code });
            coupon.value = response.data.coupon;
            return response.data;
        } catch (error) {
            console.error('Failed to apply coupon:', error);
            throw error;
        } finally {
            loading.value = false;
        }
    }

    async function removeCoupon() {
        loading.value = true;
        try {
            const response = await axios.delete('/api/cart/coupon');
            coupon.value = null;
            items.value = response.data.items;
        } catch (error) {
            console.error('Failed to remove coupon:', error);
            throw error;
        } finally {
            loading.value = false;
        }
    }

    async function clearCart() {
        loading.value = true;
        try {
            await axios.delete('/api/cart/clear');
            items.value = [];
            coupon.value = null;
        } catch (error) {
            console.error('Failed to clear cart:', error);
            throw error;
        } finally {
            loading.value = false;
        }
    }

    return {
        items,
        loading,
        coupon,
        settings,
        itemCount,
        subtotal,
        discount,
        tax,
        shipping,
        total,
        fetchSettings,
        fetchCart,
        addItem,
        updateItem,
        removeItem,
        applyCoupon,
        removeCoupon,
        clearCart,
    };
});