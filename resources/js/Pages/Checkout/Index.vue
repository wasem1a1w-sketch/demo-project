<template>
    <ShopLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center gap-4 mb-8">
            <Link :href="route('cart')" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </Link>
            <h1 class="text-3xl font-bold">Checkout</h1>
        </div>

        <div v-if="cartStore.items.length === 0" class="text-center py-12">
            <p class="text-gray-500 mb-4">Your cart is empty</p>
            <Link :href="route('shop')" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700">
                Continue Shopping
            </Link>
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-6">
                <!-- Saved Addresses -->
                <div v-if="user && savedAddresses.length > 0" class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold mb-4">Saved Addresses</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                        <div v-for="addr in savedAddresses" :key="addr.id"
                            @click="selectAddress(addr)"
                            class="border rounded-lg p-4 cursor-pointer hover:border-indigo-500 transition-colors"
                            :class="selectedAddressId === addr.id ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'">
                            <p class="font-medium text-sm">{{ addr.first_name }} {{ addr.last_name }}</p>
                            <p v-if="addr.company" class="text-xs text-gray-500">{{ addr.company }}</p>
                            <p class="text-xs text-gray-500">{{ addr.address1 }}</p>
                            <p v-if="addr.address2" class="text-xs text-gray-500">{{ addr.address2 }}</p>
                            <p class="text-xs text-gray-500">{{ addr.city }}, {{ addr.state }} {{ addr.postal_code }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ addr.phone }}</p>
                            <p v-if="addr.is_default" class="text-xs text-indigo-600 mt-1">Default</p>
                        </div>
                        <div @click="clearAddress"
                            class="border border-dashed border-gray-300 rounded-lg p-4 cursor-pointer hover:border-indigo-500 transition-colors flex items-center justify-center"
                            :class="selectedAddressId === null ? 'border-indigo-500 bg-indigo-50' : ''">
                            <span class="text-sm text-gray-500">+ Add New Address</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold mb-4">Shipping Information</h2>
                    <form @submit.prevent="placeOrder" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                                <input v-model="form.first_name" type="text" required
                                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                                <input v-model="form.last_name" type="text" required
                                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Company (optional)</label>
                            <input v-model="form.company" type="text"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address *</label>
                            <input v-model="form.address1" type="text" required
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Apartment, suite, etc. (optional)</label>
                            <input v-model="form.address2" type="text"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                                <input v-model="form.city" type="text" required
                                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">State *</label>
                                <input v-model="form.state" type="text" required
                                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code *</label>
                                <input v-model="form.postal_code" type="text" required
                                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Country *</label>
                                <input v-model="form.country" type="text" required
                                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                            <input v-model="form.phone" type="tel" required
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </form>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold mb-4">Payment Method</h2>
                    <div class="space-y-3">
                        <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input v-model="form.payment_method" type="radio" value="stripe" class="mr-3">
                            <div>
                                <span class="font-medium">Credit/Debit Card</span>
                                <p class="text-sm text-gray-500">Pay with Stripe</p>
                            </div>
                        </label>
                        <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input v-model="form.payment_method" type="radio" value="paypal" class="mr-3">
                            <div>
                                <span class="font-medium">PayPal</span>
                                <p class="text-sm text-gray-500">Pay with PayPal</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <div>
                <div class="bg-white rounded-lg shadow-sm p-6 sticky top-24">
                    <h2 class="text-lg font-semibold mb-4">Order Summary</h2>

                    <div class="space-y-3 text-sm mb-4">
                        <div v-for="item in cartStore.items" :key="item.id" class="flex justify-between">
                            <span class="text-gray-600">{{ item.quantity }}x {{ item.product?.name }}</span>
                            <span>${{ (item.product?.price * item.quantity).toFixed(2) }}</span>
                        </div>
                    </div>

                    <div class="border-t pt-3 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span>${{ cartStore.subtotal.toFixed(2) }}</span>
                        </div>
                        <div v-if="cartStore.discount > 0" class="flex justify-between text-green-600">
                            <span>Discount</span>
                            <span>-${{ cartStore.discount.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Tax</span>
                            <span>${{ cartStore.tax.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Shipping</span>
                            <span>{{ cartStore.shipping === 0 ? 'FREE' : '$' + cartStore.shipping.toFixed(2) }}</span>
                        </div>
                        <div class="border-t pt-2 flex justify-between font-bold text-lg">
                            <span>Total</span>
                            <span>${{ cartStore.total.toFixed(2) }}</span>
                        </div>
                    </div>

                    <button @click="placeOrder" :disabled="placing"
                        class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 disabled:opacity-50 mt-6">
                        {{ placing ? 'Processing...' : 'Place Order' }}
                    </button>

                    <p v-if="errorMessage" class="text-red-500 text-sm mt-2">{{ errorMessage }}</p>
                </div>
            </div>
        </div>
    </div>
</ShopLayout>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useCartStore } from '../../Stores/cart';
import { useNotification } from '../../composables/useNotification';
import ShopLayout from '../../Layouts/ShopLayout.vue';

const page = usePage();
const cartStore = useCartStore();
const { error: showError } = useNotification();
const placing = ref(false);
const errorMessage = ref('');
const savedAddresses = ref([]);
const selectedAddressId = ref(null);

const user = computed(() => page.props.auth?.user);

const form = reactive({
    first_name: '',
    last_name: '',
    company: '',
    address1: '',
    address2: '',
    city: '',
    state: '',
    postal_code: '',
    country: 'US',
    phone: '',
    payment_method: 'stripe',
});

onMounted(async () => {
    await cartStore.fetchCart();
    if (user.value) {
        await fetchSavedAddresses();
    }
});

async function fetchSavedAddresses() {
    try {
        const response = await axios.get('/addresses');
        savedAddresses.value = response.data.addresses || [];
    } catch (e) {
        console.error('Failed to load saved addresses', e);
    }
}

function selectAddress(addr) {
    selectedAddressId.value = addr.id;
    form.first_name = addr.first_name;
    form.last_name = addr.last_name;
    form.company = addr.company || '';
    form.address1 = addr.address1;
    form.address2 = addr.address2 || '';
    form.city = addr.city;
    form.state = addr.state;
    form.postal_code = addr.postal_code;
    form.country = addr.country;
    form.phone = addr.phone;
}

function clearAddress() {
    selectedAddressId.value = null;
    form.first_name = '';
    form.last_name = '';
    form.company = '';
    form.address1 = '';
    form.address2 = '';
    form.city = '';
    form.state = '';
    form.postal_code = '';
    form.country = 'US';
    form.phone = '';
}

async function placeOrder() {
    placing.value = true;
    errorMessage.value = '';

    try {
        if (cartStore.items.length === 0) {
            await cartStore.fetchCart();
        }

        if (cartStore.items.length === 0) {
            errorMessage.value = 'Your cart is empty. Please add items to your cart before checkout.';
            showError(errorMessage.value);
            return;
        }

        const orderData = {
            first_name: form.first_name,
            last_name: form.last_name,
            company: form.company,
            address1: form.address1,
            address2: form.address2,
            city: form.city,
            state: form.state,
            postal_code: form.postal_code,
            country: form.country,
            phone: form.phone,
            payment_method: form.payment_method,
            items: cartStore.items.map(item => ({
                id: item.id,
                product_id: item.product_id,
                quantity: item.quantity,
                price: item.product?.price || 0
            })),
            subtotal: Number(cartStore.subtotal),
            tax: Number(cartStore.tax),
            shipping: Number(cartStore.shipping),
            discount: Number(cartStore.discount),
            total: Number(cartStore.total),
            coupon_id: cartStore.coupon?.id || null,
        };

        const response = await axios.post('/api/orders', orderData);
        cartStore.items = [];
        cartStore.coupon = null;
        router.visit(route('orders.show', { orderNumber: response.data.order_number }));
    } catch (e) {
        console.error('Order error:', e.response?.data || e.message);
        errorMessage.value = e.response?.data?.message || 'Failed to place order';
        if (e.response?.data?.errors) {
            const errors = Object.values(e.response.data.errors).flat();
            errorMessage.value = errors.join(', ');
        }
        showError(errorMessage.value);
    } finally {
        placing.value = false;
    }
}
</script>
