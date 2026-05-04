<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
        <Notifications />
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="text-center text-3xl font-bold">Sign in to your account</h2>
            </div>
            <form class="mt-8 space-y-6" @submit.prevent="submit">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input v-model="form.email" type="email" required
                            class="mt-1 block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                            :class="{ 'border-red-500 focus:ring-red-500 focus:outline-none': hasError }">
                        <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input v-model="form.password" type="password" required
                            class="mt-1 block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                            :class="{ 'border-red-500 focus:ring-red-500 focus:outline-none': hasError }">
                    </div>
                </div>
                <button type="submit" :disabled="processing"
                    class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-indigo-600">
                    <span v-if="processing">Signing in...</span>
                    <span v-else>Sign in</span>
                </button>
            </form>
            <p class="text-center">
                Don't have an account?
                <Link :href="route('register')" class="text-indigo-600 hover:text-indigo-700">Register</Link>
            </p>
        </div>
    </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import { useNotification } from '../../composables/useNotification';
import Notifications from '../../components/Notifications.vue';
import { ref, reactive, computed, watch, onMounted } from 'vue';

const props = defineProps({
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const { error } = useNotification();

const processing = ref(false);
const form = reactive({
    email: '',
    password: '',
});

const hasError = computed(() => props.errors.email || props.errors.password);
let previousErrors = '';

onMounted(() => {
    previousErrors = JSON.stringify(props.errors);
    if (props.errors.email || props.errors.password) {
        error(props.errors.email || props.errors.password);
    }
});

watch(() => props.errors, (newErrors) => {
    const currentErrors = JSON.stringify(newErrors);
    if (currentErrors !== previousErrors && (newErrors.email || newErrors.password)) {
        const msg = newErrors.email || newErrors.password;
        error(msg);
        previousErrors = currentErrors;
    }
}, { deep: true });

function submit() {
    if (hasError.value) {
        error(props.errors.email || props.errors.password);
    }
    
    processing.value = true;
    router.post(route('login'), form, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>