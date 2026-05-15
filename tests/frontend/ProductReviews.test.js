import { mount } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

vi.stubGlobal('route', (name) => `/${name}`)

const mockUsePage = vi.hoisted(() => vi.fn(() => ({
    props: {
        auth: { user: null },
        ziggy: { url: '/shop/product/test' },
    },
})))

vi.mock('@inertiajs/vue3', () => ({
    usePage: mockUsePage,
    Link: { render: () => null },
}))

vi.mock('axios', () => ({
    default: {
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
        defaults: { headers: { common: {} } },
    },
}))

import axios from 'axios'
import ProductReviews from '../../resources/js/components/ProductReviews.vue'

function createWrapper(props = {}) {
    return mount(ProductReviews, {
        props: { productId: 1, ...props },
    })
}

describe('ProductReviews', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
        axios.get.mockResolvedValue({ data: { data: [] } })
    })

    it('shows login prompt when user is not authenticated', async () => {
        mockUsePage.mockReturnValue({
            props: {
                auth: { user: null },
                ziggy: { url: '/shop/product/test' },
            },
        })

        const wrapper = createWrapper()
        await new Promise(r => setTimeout(r, 50))
        expect(wrapper.text()).toContain('to leave a review')
    })

    it('shows review form when user is authenticated', async () => {
        mockUsePage.mockReturnValue({
            props: {
                auth: { user: { id: 1, name: 'Test User' } },
                ziggy: { url: '/shop/product/test' },
            },
        })

        const wrapper = createWrapper()
        await new Promise(r => setTimeout(r, 50))
        expect(wrapper.find('form').exists()).toBe(true)
        expect(wrapper.text()).toContain('Submit Review')
    })

    it('renders existing reviews loaded from API', async () => {
        const reviews = [
            { id: 1, rating: 5, title: 'Amazing!', body: 'Love it', user: { name: 'Alice' } },
            { id: 2, rating: 3, title: 'Okay', body: 'Works fine', user: { name: 'Bob' } },
        ]

        axios.get.mockResolvedValue({ data: { data: reviews } })

        const wrapper = createWrapper()
        await new Promise(r => setTimeout(r, 50))

        expect(wrapper.text()).toContain('Amazing!')
        expect(wrapper.text()).toContain('Alice')
        expect(wrapper.text()).toContain('Okay')
        expect(wrapper.text()).toContain('Bob')
    })

    it('displays average rating from props', async () => {
        axios.get.mockResolvedValue({ data: { data: [] } })

        const wrapper = createWrapper({ avgRating: 4.0, reviewsCount: 2 })
        await new Promise(r => setTimeout(r, 50))

        expect(wrapper.text()).toContain('4.0')
        expect(wrapper.text()).toContain('2 reviews')
    })

    it('submits review via API when form is submitted', async () => {
        mockUsePage.mockReturnValue({
            props: {
                auth: { user: { id: 1, name: 'Test User' } },
                ziggy: { url: '/shop/product/test' },
            },
        })

        axios.get.mockResolvedValue({ data: { data: [] } })
        axios.post.mockResolvedValue({
            data: { id: 3, rating: 4, title: 'Nice', body: 'Good', user: { name: 'Test User' } },
        })

        const wrapper = createWrapper()
        await new Promise(r => setTimeout(r, 50))

        const stars = wrapper.findAll('button[type="button"]')
        await stars[3].trigger('click')

        await wrapper.find('#review-title').setValue('Nice')
        await wrapper.find('#review-body').setValue('Good')
        await wrapper.find('form').trigger('submit.prevent')

        await new Promise(r => setTimeout(r, 50))

        expect(axios.post).toHaveBeenCalledWith('/api/products/1/reviews', {
            rating: 4,
            title: 'Nice',
            body: 'Good',
        })
    })
})
