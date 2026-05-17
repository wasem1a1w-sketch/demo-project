<template>
    <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Coupons</h1>
            <Link v-if="can('coupons.create')" :href="route('admin.coupons.create')" class="btn-primary">
                Add Coupon
            </Link>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Value</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Used</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Valid Until</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="coupon in coupons.data" :key="coupon.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white font-mono">{{ coupon.code }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 capitalize">{{ coupon.type }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ coupon.type === 'percentage' ? coupon.value + '%' : '$' + coupon.value }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ coupon.used_count }}{{ coupon.usage_limit ? ' / ' + coupon.usage_limit : '' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ coupon.valid_until ? new Date(coupon.valid_until).toLocaleDateString() : 'No expiry' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="coupon.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Active</span>
                                <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">Inactive</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <Link v-if="can('coupons.update')" :href="route('admin.coupons.edit', { coupon: coupon.id })" class="inline-flex items-center px-3 py-1 bg-indigo-600 dark:bg-indigo-500 border border-transparent rounded-md font-semibold text-xs text-white mr-2 hover:bg-indigo-500">Edit</Link>
                                <button v-if="can('coupons.delete')" @click="deleteCoupon(coupon.id)" class="inline-flex items-center px-3 py-1 bg-red-600 dark:bg-red-500 border border-transparent rounded-md font-semibold text-xs text-white hover:bg-red-500">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="coupons.data.length === 0">
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No coupons yet</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="coupons.last_page > 1" class="flex justify-center mt-6 gap-2">
            <button v-for="page in coupons.last_page" :key="page" @click="goToPage(page)"
                :class="page === coupons.current_page ? 'bg-indigo-600 text-white px-3 py-1 rounded-md text-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-3 py-1 rounded-md text-sm border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'">
                {{ page }}
            </button>
        </div>

        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Coupon"
            message="Are you sure you want to delete this coupon?"
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

const { can } = usePermission();
const showDeleteModal = ref(false);
const deletingCouponId = ref(null);

defineProps({
    coupons: Object,
});

function deleteCoupon(id) {
    deletingCouponId.value = id;
    showDeleteModal.value = true;
}

function confirmDelete() {
    showDeleteModal.value = false;
    router.delete(route('admin.coupons.destroy', { coupon: deletingCouponId.value }), {
        onSuccess: () => router.reload({ only: ['coupons'] }),
    });
}

function goToPage(page) {
    router.get(route('admin.coupons', { page }));
}
</script>
