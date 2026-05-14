<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
        <div class="max-w-md w-full space-y-8 text-center">
            <div>
                <div class="mx-auto w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold">Verify your email</h2>
                <p class="mt-4 text-gray-600">
                    We sent a verification link to <strong>{{ user?.email }}</strong>.
                </p>
                <p class="mt-2 text-gray-500 text-sm">Click the link in the email to activate your account.</p>
            </div>
            <div v-if="$page.props.flash?.success" class="bg-green-50 text-green-700 p-4 rounded-lg text-sm">
                {{ $page.props.flash.success }}
            </div>
            <div class="mt-8 space-y-4">
                <button @click="resend" :disabled="sending"
                    class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 disabled:opacity-50">
                    <span v-if="sending">Sending...</span>
                    <span v-else>Resend Verification Email</span>
                </button>
                <button @click="logout" class="w-full text-gray-600 py-2 text-sm hover:text-gray-800">
                    Sign out
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();
const sending = ref(false);

const user = computed(() => page.props.auth?.user);

function resend() {
    sending.value = true;
    router.post(route('verification.resend'), {}, {
        preserveScroll: true,
        onFinish: () => {
            sending.value = false;
        },
    });
}

function logout() {
    router.post(route('logout'));
}
</script>
