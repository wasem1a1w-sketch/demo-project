<template>
    <ShopLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="max-w-2xl mx-auto">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Contact Us</h1>
                <p class="text-gray-500 dark:text-gray-400 mb-8">Have a question or feedback? We'd love to hear from you.</p>

                <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-700 dark:text-green-400 text-sm">
                    {{ $page.props.flash.success }}
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 sm:p-8">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                            <input id="name" v-model="form.name" type="text" required
                                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name }}</p>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                            <input id="email" v-model="form.email" type="email" required
                                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <p v-if="errors.email" class="text-red-500 text-xs mt-1">{{ errors.email }}</p>
                        </div>

                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message</label>
                            <textarea id="message" v-model="form.message" rows="5" required
                                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            <p v-if="errors.message" class="text-red-500 text-xs mt-1">{{ errors.message }}</p>
                        </div>

                        <button type="submit" :disabled="processing"
                            class="w-full bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium">
                            {{ processing ? 'Sending...' : 'Send Message' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </ShopLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import ShopLayout from '../../Layouts/ShopLayout.vue';

const processing = ref(false);
const form = reactive({ name: '', email: '', message: '' });
const errors = reactive({});

function submit() {
    processing.value = true;
    errors.name = '';
    errors.email = '';
    errors.message = '';

    router.post(route('contact'), form, {
        onSuccess: () => {
            form.name = '';
            form.email = '';
            form.message = '';
            processing.value = false;
        },
        onError: (errs) => {
            Object.assign(errors, errs);
            processing.value = false;
        },
    });
}
</script>
