<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

defineProps({
    src: String,
    alt: { type: String, default: '' },
    imgClass: { type: String, default: '' }, // Passes layout classes from the parent grid
});

const isIntersecting = ref(false);
const imageRef = ref(null);
let observer;

onMounted(() => {
    observer = new IntersectionObserver(([entry]) => {
        if (entry.isIntersecting) {
            isIntersecting.value = true;
            observer.disconnect(); // Stop observing once loaded
        }
    }, { rootMargin: '50px' }); // Starts loading 50px before it enters the screen

    if (imageRef.value) observer.observe(imageRef.value);
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});
</script>

<template>
  <div 
    ref="imageRef" 
    :class="['relative flex items-center justify-center overflow-hidden w-full h-full bg-gray-100 dark:bg-gray-800', !isIntersecting ? 'animate-pulse' : '']"
  >
    <template v-if="isIntersecting">
      <img 
        :src="src" 
        class="absolute inset-0 w-full h-full object-cover filter blur-xl scale-110 opacity-30 pointer-events-none" 
      />

      <img 
        :src="src" 
        :alt="alt" 
        :class="['relative z-10 max-w-full max-h-full object-contain transition-opacity duration-300', imgClass]" 
      />
    </template>
  </div>
</template>