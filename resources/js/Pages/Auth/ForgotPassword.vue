<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="text-center text-3xl font-bold">Forgot your password?</h2>
                <p class="mt-2 text-center text-gray-600">Enter your email and we'll send you a reset link.</p>
            </div>
            <div v-if="$page.props.flash?.success"
                class="bg-green-50 text-green-700 p-4 rounded-lg text-sm text-center">
                {{ $page.props.flash.success }}
            </div>
            <form v-else class="mt-8 space-y-6" @submit.prevent="submit">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input v-model="form.email" type="email" required
                        class="mt-1 block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                        :class="{ 'border-red-500': errors.email }">
                    <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
                </div>
                <button type="submit" :disabled="processing"
                    class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 disabled:opacity-50">
                    <span v-if="processing">Sending...</span>
                    <span v-else>Send Reset Link</span>
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
});

const processing = ref(false);
const form = reactive({
    email: '',
});

function submit() {
    processing.value = true;
    router.post(route('password.email'), form, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>
