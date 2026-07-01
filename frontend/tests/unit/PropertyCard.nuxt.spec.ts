// Component test for PropertyCard. Run with: npx vitest
// (uses @vue/test-utils; NuxtLink is stubbed).
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import PropertyCard from '~/components/PropertyCard.vue'
import type { Property } from '~/types'

const property = {
  id: 1,
  title: 'Hillside Glass Villa',
  slug: 'hillside-glass-villa',
  transaction_type: 'buy',
  status: 'active',
  price: { amount: 12500000, unit: 'total', negotiable: false, formatted: 'Rs. 1.25 Cr' },
  area: { size: 6, unit: 'aana' },
  specs: { bedrooms: 5, bathrooms: 6, floors: 3, parking: 2, road_width: 20, facing: 'East' },
  flags: { is_featured: true, is_exclusive: false, is_emerging: false, is_open_house: false, is_by_owner: false },
  open_house_date: null,
  location: { address: null, latitude: null, longitude: null, city: { name: 'Kathmandu' } as any },
  primary_image: 'https://example.com/x.jpg',
  views: 10,
  published_at: null,
  created_at: '2026-01-01',
  url: '/property/hillside-glass-villa',
} as unknown as Property

const stubs = { NuxtLink: { template: '<a :href="to"><slot /></a>', props: ['to'] } }

describe('PropertyCard', () => {
  it('renders title, price and the Featured tag', () => {
    const w = mount(PropertyCard, { props: { property }, global: { stubs } })
    expect(w.text()).toContain('Hillside Glass Villa')
    expect(w.text()).toContain('Rs. 1.25 Cr')
    expect(w.text()).toContain('Featured')
  })

  it('links to the property detail url', () => {
    const w = mount(PropertyCard, { props: { property }, global: { stubs } })
    expect(w.find('a').attributes('href')).toBe('/property/hillside-glass-villa')
  })

  it('shows the spec line', () => {
    const w = mount(PropertyCard, { props: { property }, global: { stubs } })
    expect(w.text()).toContain('5 Bd')
    expect(w.text()).toContain('6 Ba')
  })
})
