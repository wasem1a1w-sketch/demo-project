<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

defineProps({
    src: String,
    alt: { type: String, default: '' },
    imgClass: { type: String, default: '' },
});

const isIntersecting = ref(false);
const imageRef = ref(null);
let observer;

onMounted(() => {
    observer = new IntersectionObserver(([entry]) => {
        if (entry.isIntersecting) {
            isIntersecting.value = true;
            observer.disconnect();
        }
    }, { rootMargin: '50px' });
    if (imageRef.value) observer.observe(imageRef.value);
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});
</script>

<template>
  <div ref="imageRef" class="bg-gray-200 animate-pulse w-full h-full flex items-center justify-center overflow-hidden">
    <img v-if="isIntersecting" :src="src" :alt="alt" :class="['w-full h-full object-cover transition-opacity duration-300', imgClass]" />
  </div>
</template>
