<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="text-center text-3xl font-bold">Reset your password</h2>
            </div>
            <form class="mt-8 space-y-6" @submit.prevent="submit">
                <input type="hidden" v-model="form.token">
                <input type="hidden" v-model="form.email">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input :value="form.email" type="email" disabled
                        class="mt-1 block w-full px-3 py-2 border rounded-lg bg-gray-100 text-gray-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">New Password</label>
                    <input v-model="form.password" type="password" required
                        class="mt-1 block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                    <input v-model="form.password_confirmation" type="password" required
                        class="mt-1 block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
                <button type="submit" :disabled="processing"
                    class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 disabled:opacity-50">
                    <span v-if="processing">Resetting...</span>
                    <span v-else>Reset Password</span>
                </button>
            </form>
            <p class="text-center">
                <Link :href="route('login')" class="text-indigo-600 hover:text-indigo-700">Back to sign in</Link>
            </p>
        </div>
    </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps({
    errors: {
        type: Object,
        default: () => ({}),
    },
    token: {
        type: String,
        required: true,
    },
    email: {
        type: String,
        required: true,
    },
});

const processing = ref(false);
const form = reactive({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    processing.value = true;
    router.post(route('password.update'), form, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>
