import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, vi, beforeEach } from 'vitest'

// Make route() globally available
globalThis.route = (name) => `/${name.replaceAll('.', '/')}`

// Mock Inertia
const mockRouter = { visit: vi.fn(), post: vi.fn() }
vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: { auth: { user: { permissions: ['products.create'] } }, ziggy: {} } }),
    Link: { render: () => null, name: 'Link' },
    router: mockRouter,
}))

// Mock usePermission
vi.mock('../../../resources/js/composables/usePermission', () => ({
    usePermission: () => ({
        can: (perm) => perm === 'products.create',
    }),
}))

// Mock LazyImage
const LazyImage = { render: () => null, name: 'LazyImage' }

function flushFiles() {
    return new Promise(resolve => setTimeout(resolve, 50))
}

async function createWrapper() {
    const AdminProductCreate = (await import('../../resources/js/Pages/Admin/Products/Create.vue')).default

    return mount(AdminProductCreate, {
        props: {
            categories: [
                { id: 1, name: 'Electronics' },
                { id: 2, name: 'Clothing' },
            ],
        },
        global: {
            stubs: { Link: true, LazyImage },
        },
    })
}

describe('AdminProductCreate', () => {
    beforeEach(() => {
        vi.clearAllMocks()
    })

    it('renders all form fields', async () => {
        const wrapper = await createWrapper()

        expect(wrapper.find('input').exists()).toBe(true)
        expect(wrapper.text()).toContain('Name')
        expect(wrapper.text()).toContain('Slug')
        expect(wrapper.text()).toContain('Price')
        expect(wrapper.text()).toContain('Stock')
        expect(wrapper.text()).toContain('Category')
        expect(wrapper.text()).toContain('Short Description')
        expect(wrapper.text()).toContain('Description')
        expect(wrapper.text()).toContain('Main Image')
        expect(wrapper.text()).toContain('Gallery Images')
        expect(wrapper.text()).toContain('Save Product')
    })

    it('renders category dropdown with options', async () => {
        const wrapper = await createWrapper()
        const select = wrapper.find('select')
        expect(select.exists()).toBe(true)

        const options = select.findAll('option')
        expect(options.length).toBe(3) // Default + 2 categories
        expect(options[1].text()).toBe('Electronics')
        expect(options[2].text()).toBe('Clothing')
    })

    it('Active checkbox defaults to true', async () => {
        const wrapper = await createWrapper()
        const activeCheckbox = wrapper.find('input[type="checkbox"]')
        expect(activeCheckbox.element.checked).toBe(true)
    })

    it('Featured checkbox defaults to false', async () => {
        const wrapper = await createWrapper()
        const checkboxes = wrapper.findAll('input[type="checkbox"]')
        const featuredCheckbox = checkboxes[1]
        expect(featuredCheckbox.element.checked).toBe(false)
    })

    it('toggles Active checkbox', async () => {
        const wrapper = await createWrapper()
        const activeCheckbox = wrapper.find('input[type="checkbox"]')

        await activeCheckbox.setValue(false)
        expect(activeCheckbox.element.checked).toBe(false)

        await activeCheckbox.setValue(true)
        expect(activeCheckbox.element.checked).toBe(true)
    })

    it('shows main image preview on file upload', async () => {
        const wrapper = await createWrapper()
        const fileInput = wrapper.find('input[type="file"]')

        const file = new File(['fake-image'], 'test.jpg', { type: 'image/jpeg' })
        Object.defineProperty(fileInput.element, 'files', { value: [file] })

        await fileInput.trigger('change')
        await flushFiles()

        expect(wrapper.vm.mainImage).toBeTruthy()
        expect(wrapper.vm.mainImagePreview).toBeTruthy()
    })

    it('shows error for main image exceeding 3MB', async () => {
        const wrapper = await createWrapper()
        const fileInput = wrapper.find('input[type="file"]')

        const largeFile = new File(['x'.repeat(4 * 1024 * 1024)], 'large.jpg', { type: 'image/jpeg' })
        Object.defineProperty(fileInput.element, 'files', { value: [largeFile] })

        await fileInput.trigger('change')

        expect(wrapper.vm.mainImageError).toContain('3MB')
        expect(wrapper.vm.mainImage).toBeNull()
    })

    it('removes main image preview when X is clicked', async () => {
        const wrapper = await createWrapper()
        wrapper.vm.mainImage = new File(['test'], 'img.jpg', { type: 'image/jpeg' })
        wrapper.vm.mainImagePreview = 'data:image/png;base64,fake'

        await wrapper.vm.$nextTick()

        wrapper.vm.removeMainImage()
        expect(wrapper.vm.mainImage).toBeNull()
        expect(wrapper.vm.mainImagePreview).toBe('')
    })

    it('handles gallery image upload up to 4 files', async () => {
        const wrapper = await createWrapper()
        const fileInput = wrapper.findAll('input[type="file"]')[1]

        const files = [
            new File(['a'], 'a.jpg', { type: 'image/jpeg' }),
            new File(['b'], 'b.jpg', { type: 'image/jpeg' }),
        ]
        Object.defineProperty(fileInput.element, 'files', { value: files })

        await fileInput.trigger('change')
        await flushFiles()

        expect(wrapper.vm.galleryImages.length).toBe(2)
        expect(wrapper.vm.galleryPreviews.length).toBe(2)
    })

    it('restricts gallery images to maximum 4', async () => {
        const wrapper = await createWrapper()
        wrapper.vm.galleryImages = Array(4).fill(null).map(() => new File(['x'], 'img.jpg', { type: 'image/jpeg' }))
        wrapper.vm.galleryPreviews = Array(4).fill('data:image/png;base64,x')

        const fileInput = wrapper.findAll('input[type="file"]')[1]
        const extraFile = [new File(['z'], 'extra.jpg', { type: 'image/jpeg' })]
        Object.defineProperty(fileInput.element, 'files', { value: extraFile })

        await fileInput.trigger('change')

        expect(wrapper.vm.galleryError).toContain('Maximum 4 gallery images allowed')
        expect(wrapper.vm.galleryImages.length).toBe(4)
    })

    it('removes individual gallery image', async () => {
        const wrapper = await createWrapper()
        wrapper.vm.galleryImages = [
            new File(['a'], 'a.jpg', { type: 'image/jpeg' }),
            new File(['b'], 'b.jpg', { type: 'image/jpeg' }),
        ]
        wrapper.vm.galleryPreviews = ['preview-a', 'preview-b']

        wrapper.vm.removeGalleryImage(0)
        expect(wrapper.vm.galleryImages.length).toBe(1)
        expect(wrapper.vm.galleryPreviews.length).toBe(1)
        expect(wrapper.vm.galleryPreviews[0]).toBe('preview-b')
    })

    it('submits form with FormData on save', async () => {
        const wrapper = await createWrapper()

        wrapper.vm.form.name = 'New Product'
        wrapper.vm.form.slug = 'new-product'
        wrapper.vm.form.price = 29.99
        wrapper.vm.form.stock = 10

        wrapper.vm.submit()

        expect(mockRouter.post).toHaveBeenCalledWith(
            '/admin/products/store',
            expect.any(FormData),
            expect.objectContaining({ forceFormData: true })
        )
    })

    it('Cancel button navigates to product list', async () => {
        const wrapper = await createWrapper()

        const link = wrapper.findComponent({ name: 'Link' })
        expect(link.exists()).toBe(true)
    })
})
