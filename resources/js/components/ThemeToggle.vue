<script setup>
import { ref, onMounted } from 'vue';

const isDark = ref(false);

function initTheme() {
    const stored = localStorage.getItem('theme');
    isDark.value = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
    applyTheme();
}

function applyTheme() {
    if (isDark.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
}

function toggle() {
    isDark.value = !isDark.value;
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
    applyTheme();
}

onMounted(initTheme);
</script>

<template>
    <button
        @click="toggle()"
        class="theme-toggle"
        :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
    >
        <svg v-if="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
        </svg>
        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
        </svg>
    </button>
</template>

<style scoped>
.theme-toggle {
    padding: 0.5rem;
    border-radius: 0.5rem;
    color: #9ca3af;
    transition: color 0.2s;
}
.theme-toggle:hover {
    color: #4b5563;
}
:global(.dark) .theme-toggle {
    color: #9ca3af;
}
:global(.dark) .theme-toggle:hover {
    color: #e5e7eb;
}
</style>