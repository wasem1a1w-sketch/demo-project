<template>
    <component :is="isExternal(href) ? 'a' : 'InertiaLink'" :href="isExternal(href) ? href : resolvedHref" :class="$attrs.class" :target="target">
        <slot />
    </component>
</template>

<script setup>
import { computed } from 'vue';
import { InertiaLink } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    target: {
        type: String,
        default: null,
    },
});

const isExternal = (url) => {
    return url.startsWith('http') || url.startsWith('//') || url.startsWith('www.');
};

const resolvedHref = computed(() => {
    if (isExternal(props.href)) {
        return props.href;
    }
    const baseUrl = window.__ziggy?.url || '';
    return baseUrl + props.href;
});
</script>