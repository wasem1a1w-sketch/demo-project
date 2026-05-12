import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermission() {
    const page = usePage();
    const permissions = computed(() => page.props.auth?.user?.permissions ?? []);

    console.log(permissions.value);
    function can(permission) {
        return permissions.value.includes(permission);
    }

    return { can };
}
