<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="text-center text-3xl font-bold">Create your account</h2>
            </div>
            <form class="mt-8 space-y-6" @submit.prevent="submit">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">First Name</label>
                            <input v-model="form.first_name" type="text" required
                                class="mt-1 block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input v-model="form.last_name" type="text" required
                                class="mt-1 block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input v-model="form.email" type="email" required
                            class="mt-1 block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input v-model="form.password" type="password" required
                            class="mt-1 block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input v-model="form.password_confirmation" type="password" required
                            class="mt-1 block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <button type="submit"
                    class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700">
                    Register
                </button>
            </form>
            <p class="text-center">
                Already have an account?
                <Link :href="route('login')" class="text-indigo-600 hover:text-indigo-700">Sign in</Link>
            </p>
        </div>
    </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import { useNotification } from '../../composables/useNotification';

const { error, success } = useNotification();

const form = {
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: '',
};

function submit() {
    if (form.password !== form.password_confirmation) {
        error('Passwords do not match');
        return;
    }
    router.post(route('register'), form, {
        onSuccess: () => {
            success('Registration successful!');
        },
    });
}
</script>