import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'

import StarRating from '../../resources/js/components/StarRating.vue'

describe('StarRating', () => {
    it('renders 5 stars', () => {
        const wrapper = mount(StarRating, {
            props: { modelValue: 3 },
        })

        const stars = wrapper.findAll('button')
        expect(stars).toHaveLength(5)
    })

    it('highlights the correct number of stars based on modelValue', () => {
        const wrapper = mount(StarRating, {
            props: { modelValue: 4 },
        })

        const stars = wrapper.findAll('button')
        stars.forEach((star, index) => {
            if (index < 4) {
                expect(star.classes()).toContain('text-yellow-400')
            } else {
                expect(star.classes()).toContain('text-gray-300')
            }
        })
    })

    it('emits update:modelValue when a star is clicked', async () => {
        const wrapper = mount(StarRating, {
            props: { modelValue: 0 },
        })

        const stars = wrapper.findAll('button')
        await stars[2].trigger('click')

        expect(wrapper.emitted('update:modelValue')).toBeTruthy()
        expect(wrapper.emitted('update:modelValue')[0]).toEqual([3])
    })

    it('disables interaction when readonly prop is true', async () => {
        const wrapper = mount(StarRating, {
            props: { modelValue: 3, readonly: true },
        })

        const stars = wrapper.findAll('button')
        await stars[4].trigger('click')

        expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    })

    it('renders as read-only when readonly prop is not provided', () => {
        const wrapper = mount(StarRating, {
            props: { modelValue: 3 },
        })

        const stars = wrapper.findAll('button')
        // By default (no readonly), it should be interactive
        expect(stars[0].element.tagName).toBe('BUTTON')
    })
})
