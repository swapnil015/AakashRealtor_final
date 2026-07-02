<script setup lang="ts">
import type { Amenity, Category, City, Property } from '~/types'
import type { Pagination } from '~/composables/useApi'

const route = useRoute()
const api = useApi()

// ── Parse the SEO segment: "buyHouse" -> {txn:'buy', categoryName:'House'} ──
function parseSegment(seg: string): { txn: 'buy' | 'rent'; category?: string } {
  const m = seg.match(/^(buy|rent)(.*)$/i)
  if (!m) return { txn: 'buy' }
  return { txn: m[1].toLowerCase() as 'buy' | 'rent', category: m[2] ? m[2].toLowerCase() : undefined }
}
// "Kathmandu-53" -> public_id 53
function parseCity(token?: string): string | undefined {
  if (!token) return undefined
  const m = token.match(/-(\d+)$/)
  return m ? m[1] : undefined
}

const parsed = parseSegment(String(route.params.segment))
const cityId = parseCity(route.params.city as string | undefined)

// Reactive filter model (seeded from the URL).
const filters = reactive<Record<string, any>>({
  transaction_type: parsed.txn,
  category: parsed.category,
  city: cityId,
  min_price: null,
  max_price: null,
  bedrooms: null,
  bathrooms: null,
  amenities: [] as string[],
})
const sort = ref('newest')
const page = ref(Number(route.query.page) || 1)

// Reference data for the sidebar.
const { data: refs } = await useAsyncData('listing-refs', async () => {
  const [cats, cities, ams] = await Promise.all([
    api.get<Category[]>('/categories'),
    api.get<City[]>('/cities'),
    api.get<Amenity[]>('/amenities'),
  ])
  return { categories: cats.data, cities: cities.data, amenities: ams.data }
})

// The listing query — re-runs on page/sort/filter apply.
const { data: result, pending, refresh } = await useAsyncData(
  'listing',
  () => api.get<Property[]>('/properties', {
    ...cleanParams(filters),
    sort: sort.value,
    page: page.value,
    per_page: 12,
  }),
  { watch: [page, sort] },
)

function cleanParams(obj: Record<string, any>) {
  const out: Record<string, any> = {}
  for (const [k, v] of Object.entries(obj)) {
    if (v === null || v === undefined || v === '' || (Array.isArray(v) && !v.length)) continue
    out[k] = v
  }
  return out
}

const properties = computed(() => result.value?.data ?? [])
const pagination = computed<Pagination | undefined>(() => result.value?.meta?.pagination)

function applyFilters() {
  page.value = 1
  refresh()
}
function resetFilters() {
  Object.assign(filters, {
    transaction_type: parsed.txn, category: undefined, city: cityId,
    min_price: null, max_price: null, bedrooms: null, bathrooms: null, amenities: [],
  })
  applyFilters()
}
function goPage(p: number) {
  page.value = p
  if (import.meta.client) window.scrollTo({ top: 0, behavior: 'smooth' })
}

// SEO: "Buy House in Kathmandu" templated title.
const cityName = computed(() => refs.value?.cities.find((c) => String(c.public_id) === cityId)?.name)
const catName = computed(() => parsed.category
  ? parsed.category.charAt(0).toUpperCase() + parsed.category.slice(1)
  : 'Property')
const heading = computed(
  () => `${parsed.txn === 'rent' ? 'Rent' : 'Buy'} ${catName.value}${cityName.value ? ` in ${cityName.value}` : ''}`,
)
useSeoMeta({
  title: heading,
  description: () =>
    `Browse ${heading.value.toLowerCase()} on Aakash Realtor — verified listings with transparent pricing and lalpurja checks.`,
})
</script>

<template>
  <div class="mx-auto max-w-shell px-margin-mobile pb-24 pt-10 md:px-margin-desktop">
    <header class="mb-8 border-b border-outline-variant pb-6">
      <p class="eyebrow mb-2">{{ pagination?.total ?? 0 }} Properties Found</p>
      <h1 class="font-display text-headline-md text-primary md:text-display-lg-mobile">{{ heading }}</h1>
    </header>

    <div class="grid gap-content-gap lg:grid-cols-[280px_1fr]">
      <FilterSidebar
        v-if="refs"
        v-model="filters"
        :categories="refs.categories"
        :cities="refs.cities"
        :amenities="refs.amenities"
        class="h-fit lg:sticky lg:top-24"
        @apply="applyFilters"
        @reset="resetFilters"
      />

      <div>
        <div class="mb-6 flex items-center justify-between border-b border-outline-variant pb-3">
          <span class="font-sans text-label-caps uppercase tracking-[0.1em] text-outline">
            Showing {{ pagination?.from ?? 0 }}–{{ pagination?.to ?? 0 }} of {{ pagination?.total ?? 0 }}
          </span>
          <select v-model="sort" class="border-0 bg-transparent font-sans text-label-caps uppercase tracking-[0.1em] text-primary outline-none">
            <option value="newest">Sort: Newest</option>
            <option value="price_asc">Sort: Price ↑</option>
            <option value="price_desc">Sort: Price ↓</option>
            <option value="popular">Sort: Popular</option>
          </select>
        </div>

        <div v-if="pending" class="grid gap-x-gutter gap-y-content-gap sm:grid-cols-2 xl:grid-cols-3">
          <div v-for="n in 6" :key="n" class="aspect-[4/3] animate-pulse bg-surface-container" />
        </div>

        <div v-else-if="properties.length" class="grid gap-x-gutter gap-y-content-gap sm:grid-cols-2 xl:grid-cols-3">
          <PropertyCard v-for="p in properties" :key="p.id" :property="p" />
        </div>

        <div v-else class="border border-outline-variant bg-surface-container-lowest p-16 text-center">
          <p class="font-display text-headline-sm text-primary">No properties match these filters.</p>
          <p class="mt-2 font-sans text-body-md text-on-surface-variant">Try widening your search, or post a requirement.</p>
          <NuxtLink to="/requirements" class="btn-primary mt-6">Post a Requirement</NuxtLink>
        </div>

        <Pagination v-if="pagination" :pagination="pagination" @change="goPage" />
      </div>
    </div>
  </div>
</template>
