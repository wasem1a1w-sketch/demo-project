import { ref } from 'vue';

const notifications = ref([]);
let id = 0;

export function useNotification() {
    function show(message, type = 'info', duration = 4000) {
        const notification = {
            id: ++id,
            message,
            type
        };
        notifications.value.push(notification);
        
        if (duration > 0) {
            setTimeout(() => {
                remove(notification.id);
            }, duration);
        }
        
        return notification.id;
    }
    
    function remove(id) {
        notifications.value = notifications.value.filter(n => n.id !== id);
    }
    
    function success(message) {
        return show(message, 'success');
    }
    
    function error(message) {
        return show(message, 'error', 6000);
    }
    
    function warning(message) {
        return show(message, 'warning', 5000);
    }
    
    return {
        notifications,
        show,
        remove,
        success,
        error,
        warning
    };
}