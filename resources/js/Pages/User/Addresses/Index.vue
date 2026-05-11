<template>
    <ShopLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between mb-8">
                <h1 class="text-3xl font-bold">My Addresses</h1>
                <button @click="showForm = !showForm; editing = null" class="btn-primary text-sm">
                    {{ showForm && !editing ? 'Cancel' : 'Add New Address' }}
                </button>
            </div>

            <div v-if="showForm" class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <h2 class="text-lg font-semibold mb-4">{{ editing ? 'Edit Address' : 'New Address' }}</h2>
                <form @submit.prevent="submitForm" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                            <input v-model="form.first_name" type="text" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                            <input v-model="form.last_name" type="text" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Company (optional)</label>
                        <input v-model="form.company" type="text" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address *</label>
                        <input v-model="form.address1" type="text" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Apartment, suite, etc. (optional)</label>
                        <input v-model="form.address2" type="text" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                            <input v-model="form.city" type="text" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">State *</label>
                            <input v-model="form.state" type="text" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code *</label>
                            <input v-model="form.postal_code" type="text" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Country *</label>
                            <input v-model="form.country" type="text" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                        <input v-model="form.phone" type="tel" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2">
                            <input v-model="form.is_default" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm">Set as default {{ form.type }} address</span>
                        </label>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" :disabled="saving" class="btn-primary disabled:opacity-50">
                            {{ saving ? 'Saving...' : (editing ? 'Update Address' : 'Save Address') }}
                        </button>
                        <button type="button" @click="cancelForm" class="btn-secondary">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <div v-if="props.addresses.length === 0 && !showForm" class="text-center py-12">
                <p class="text-gray-500 mb-4">You haven't saved any addresses yet.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="addr in props.addresses" :key="addr.id"
                    class="bg-white rounded-lg shadow-sm p-6 relative">
                    <div v-if="addr.is_default" class="absolute top-3 right-3">
                        <span class="bg-indigo-100 text-indigo-700 text-xs px-2 py-1 rounded-full">Default</span>
                    </div>
                    <p class="font-semibold">{{ addr.first_name }} {{ addr.last_name }}</p>
                    <p v-if="addr.company" class="text-sm text-gray-600">{{ addr.company }}</p>
                    <p class="text-sm text-gray-600">{{ addr.address1 }}</p>
                    <p v-if="addr.address2" class="text-sm text-gray-600">{{ addr.address2 }}</p>
                    <p class="text-sm text-gray-600">{{ addr.city }}, {{ addr.state }} {{ addr.postal_code }}</p>
                    <p class="text-sm text-gray-600">{{ addr.country }}</p>
                    <p class="text-sm text-gray-600 mt-1">{{ addr.phone }}</p>
                    <div class="flex gap-2 mt-4">
                        <button @click="editAddress(addr)" class="inline-flex items-center px-3 py-1 bg-indigo-600 dark:bg-indigo-500 border border-transparent rounded-md font-semibold text-xs text-white dark:text-indigo-100 hover:bg-indigo-500 dark:hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">Edit</button>
                        <button @click="deleteAddress(addr.id)" class="inline-flex items-center px-3 py-1 bg-red-600 dark:bg-red-500 border border-transparent rounded-md font-semibold text-xs text-white dark:text-red-100 hover:bg-red-500 dark:hover:bg-red-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </ShopLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import ShopLayout from '../../../Layouts/ShopLayout.vue';

const props = defineProps({ addresses: Array });
const page = usePage();

const showForm = ref(false);
const editing = ref(null);
const saving = ref(false);

const form = reactive({
    type: 'shipping',
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
    is_default: false,
});

function resetForm() {
    Object.assign(form, {
        type: 'shipping',
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
        is_default: false,
    });
    editing.value = null;
}

function editAddress(addr) {
    Object.assign(form, {
        type: addr.type || 'shipping',
        first_name: addr.first_name,
        last_name: addr.last_name,
        company: addr.company || '',
        address1: addr.address1,
        address2: addr.address2 || '',
        city: addr.city,
        state: addr.state,
        postal_code: addr.postal_code,
        country: addr.country,
        phone: addr.phone,
        is_default: !!addr.is_default,
    });
    editing.value = addr.id;
    showForm.value = true;
}

function cancelForm() {
    resetForm();
    showForm.value = false;
}

function submitForm() {
    saving.value = true;
    const data = { ...form };
    if (editing.value) {
        router.put(route('addresses.update', { id: editing.value }), data, {
            preserveScroll: true,
            onSuccess: () => {
                showForm.value = false;
                resetForm();
                saving.value = false;
            },
            onError: () => { saving.value = false; },
        });
    } else {
        router.post(route('addresses.store'), data, {
            preserveScroll: true,
            onSuccess: () => {
                showForm.value = false;
                resetForm();
                saving.value = false;
            },
            onError: () => { saving.value = false; },
        });
    }
}

function deleteAddress(id) {
    if (confirm('Are you sure you want to delete this address?')) {
        router.delete(route('addresses.destroy', { id }), {
            preserveScroll: true,
        });
    }
}
</script>
