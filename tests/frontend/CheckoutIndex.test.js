import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useCartStore } from '../../resources/js/Stores/cart'

globalThis.route = (name) => `/${name.replaceAll('.', '/')}`

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({
        props: {
            auth: { user: { id: 1, name: 'Test User', email: 'test@example.com' } },
            ziggy: { url: 'http://localhost' },
        },
    }),
    Link: { render: () => null, name: 'Link' },
    router: { visit: vi.fn() },
}))

vi.mock('axios', () => ({
    default: {
        get: vi.fn(),
        post: vi.fn(),
        patch: vi.fn(),
        delete: vi.fn(),
        defaults: { headers: { common: {} } },
    },
}))

vi.mock('../../resources/js/composables/useNotification', () => ({
    useNotification: () => ({
        error: vi.fn(),
        success: vi.fn(),
        warning: vi.fn(),
        show: vi.fn(),
    }),
}))

const ShopLayout = {
    template: '<div><slot /></div>',
    name: 'ShopLayout',
}

const LazyImage = { render: () => null, name: 'LazyImage' }

async function createWrapper() {
    const CheckoutIndex = (await import('../../resources/js/Pages/Checkout/Index.vue')).default

    return mount(CheckoutIndex, {
        global: {
            plugins: [createPinia()],
            stubs: { ShopLayout, Link: true, LazyImage },
        },
    })
}

describe('CheckoutIndex', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    it('renders empty cart state', async () => {
        const axios = (await import('axios')).default
        axios.get.mockResolvedValue({ data: { items: [], coupon: null } })

        const wrapper = await createWrapper()

        expect(wrapper.text()).toContain('Your cart is empty')
    })

    it('renders checkout form with cart items', async () => {
        const axios = (await import('axios')).default
        const mockItems = [{ id: 1, product_id: 1, quantity: 2, product: { name: 'Test Product', price: 25.00 } }]
        axios.get.mockResolvedValue({ data: { items: mockItems, coupon: null } })

        const wrapper = await createWrapper()
        await flushPromises()

        expect(wrapper.find('input[type="radio"][value="stripe"]').exists()).toBe(true)
        expect(wrapper.find('input[type="radio"][value="paypal"]').exists()).toBe(true)
        expect(wrapper.find('input[type="radio"][value="offline"]').exists()).toBe(true)
        expect(wrapper.text()).toContain('Test Product')
        expect(wrapper.text()).toContain('Place Order')
    })

    it('selects Stripe as default payment method', async () => {
        const axios = (await import('axios')).default
        axios.get.mockResolvedValue({ data: { items: [{ id: 1, product_id: 1, quantity: 1, product: { name: 'Test', price: 10 } }], coupon: null } })

        const wrapper = await createWrapper()
        await flushPromises()

        const stripeRadio = wrapper.find('input[type="radio"][value="stripe"]')
        expect(stripeRadio.exists()).toBe(true)
        expect(stripeRadio.element.checked).toBe(true)
    })

    it('places order with Stripe and redirects to checkout URL', async () => {
        const axios = (await import('axios')).default
        axios.get.mockResolvedValue({ data: { items: [{ id: 1, product_id: 1, quantity: 1, product: { name: 'Test', price: 20 } }], coupon: null } })
        axios.post.mockResolvedValueOnce({ data: { order_id: 1, order_number: 'ORD-TEST456' } })
        axios.post.mockResolvedValueOnce({ data: { checkout_url: 'https://checkout.stripe.com/pay/test123', session_id: 'cs_test_123' } })

        const originalLocation = window.location
        delete window.location
        window.location = { href: '' }

        const wrapper = await createWrapper()
        await flushPromises()
        await wrapper.vm.placeOrder()
        await flushPromises()

        expect(axios.post).toHaveBeenNthCalledWith(1, '/api/orders', expect.any(Object))
        expect(axios.post).toHaveBeenNthCalledWith(2, '/api/payments/create-session', { order_id: 1 })
        expect(window.location.href).toBe('https://checkout.stripe.com/pay/test123')

        window.location = originalLocation
    })

    it('shows error when placing order with empty cart', async () => {
        const axios = (await import('axios')).default
        axios.get.mockResolvedValue({ data: { items: [], coupon: null } })

        const wrapper = await createWrapper()
        await wrapper.vm.placeOrder()

        expect(wrapper.vm.errorMessage).toContain('Your cart is empty')
    })

    it('shows error on failed order placement', async () => {
        const axios = (await import('axios')).default
        axios.get.mockResolvedValue({ data: { items: [{ id: 1, product_id: 1, quantity: 1, product: { name: 'Test', price: 20 } }], coupon: null } })
        axios.post.mockRejectedValue({ response: { data: { error: 'Payment failed' } } })

        const wrapper = await createWrapper()
        await flushPromises()
        await wrapper.vm.placeOrder()
        await flushPromises()

        expect(wrapper.vm.errorMessage).toContain('Payment failed')
    })
})
