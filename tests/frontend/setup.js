import { config } from '@vue/test-utils'
import { createPinia } from 'pinia'

// Global config for @vue/test-utils
config.global.stubs = {
    'Link': true,
    'ThemeToggle': true,
}

// Mock axios
vi.mock('axios', () => {
    return {
        default: {
            get: vi.fn(),
            post: vi.fn(),
            patch: vi.fn(),
            delete: vi.fn(),
            defaults: {
                headers: {
                    common: {},
                },
            },
        },
    }
})
