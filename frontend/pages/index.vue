<script setup lang="ts">
import type { Category, City, Property, Faq } from '~/types'

const api = useApi()

// Hero scroll parallax (image drifts slower than the page).
const { y: scrollY } = useWindowScroll()
const heroParallax = computed(() => `scale(${1 + scrollY.value * 0.0004})`)

// Search bar state.
const txn = ref<'buy' | 'rent'>('buy')
const searchCategory = ref<string>('')
const searchCity = ref<string>('')

// Server-rendered homepage data (SSR for SEO + fast first paint).
const { data } = await useAsyncData('home', async () => {
  const [featured, exclusive, latest, emerging, byOwner, cats, cities, faqs] = await Promise.all([
    api.get<Property[]>('/properties/featured', { per_page: 4 }),
    api.get<Property[]>('/properties/exclusive', { per_page: 3 }),
    api.get<Property[]>('/properties', { sort: 'newest', per_page: 9 }),
    api.get<Property[]>('/properties/emerging', { per_page: 4 }),
    api.get<Property[]>('/properties/by-owner', { per_page: 4 }),
    api.get<Category[]>('/categories'),
    api.get<City[]>('/cities', { popular: 1 }),
    api.get<Faq[]>('/faqs'),
  ])
  return {
    featured: featured.data, exclusive: exclusive.data, latest: latest.data,
    emerging: emerging.data, byOwner: byOwner.data,
    categories: cats.data, cities: cities.data, faqs: faqs.data,
  }
})

function runSearch() {
  const cat = data.value?.categories.find((c) => c.slug === searchCategory.value)
  const seg = `${txn.value}${cat ? cat.name.replace(/\s+/g, '') : 'House'}`
  const city = data.value?.cities.find((c) => String(c.public_id) === searchCity.value)
  navigateTo(city ? `/${seg}/${city.url_token}` : `/${seg}`)
}

const openFaq = ref<number | null>(0)

useSeoMeta({
  title: 'Timeless Assets for the Discerning Collector',
  description:
    "Aakash Realtor — Nepal's definitive marketplace for luxury real estate and land assets. Every listing verified against its lalpurja.",
  ogTitle: 'Aakash Realtor — Timeless Assets',
})
</script>

<template>
  <div>
    <!-- ───────────── HERO ───────────── -->
    <section class="relative h-[620px] w-full overflow-hidden sm:h-[751px]">
      <div class="absolute inset-0 bg-cover bg-center will-change-transform" :style="{
        backgroundImage: `url('https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1920&q=80')`,
        transform: heroParallax,
      }" />
      <div class="editorial-overlay absolute inset-0 flex flex-col justify-end px-margin-mobile pb-16 md:px-margin-desktop">
        <div class="mx-auto w-full max-w-shell">
          <p class="mb-3 font-sans text-label-caps uppercase tracking-[0.2em] text-surface-container-lowest">Curated Excellence</p>
          <h1 class="max-w-3xl font-display text-display-lg-mobile leading-tight text-surface md:text-display-lg">
            Timeless Assets for the Discerning Collector
          </h1>

          <!-- Search panel -->
          <div class="mt-8 max-w-3xl border border-outline-variant bg-surface/95 p-4 backdrop-blur-md">
            <div class="mb-3 flex gap-2">
              <button v-for="t in (['buy','rent'] as const)" :key="t"
                class="border px-5 py-2 font-sans text-label-caps uppercase tracking-[0.1em] transition-colors"
                :class="txn === t ? 'border-primary bg-primary text-surface' : 'border-outline-variant text-primary'"
                @click="txn = t">{{ t }}</button>
            </div>
            <div class="grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
              <select v-model="searchCategory" class="border border-outline-variant bg-surface-container-lowest px-3 py-3 font-sans text-body-md text-primary outline-none focus:border-primary">
                <option value="">Asset type</option>
                <option v-for="c in data?.categories" :key="c.id" :value="c.slug">{{ c.name }}</option>
              </select>
              <select v-model="searchCity" class="border border-outline-variant bg-surface-container-lowest px-3 py-3 font-sans text-body-md text-primary outline-none focus:border-primary">
                <option value="">All locations</option>
                <option v-for="c in data?.cities" :key="c.id" :value="String(c.public_id)">{{ c.name }}</option>
              </select>
              <button class="btn-primary whitespace-nowrap" @click="runSearch">
                <span class="material-symbols-outlined text-base">search</span> Search
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ───────────── INTENT PILLS ───────────── -->
    <section class="mx-auto flex max-w-shell gap-4 overflow-x-auto px-margin-mobile py-8 hide-scrollbar md:px-margin-desktop">
      <NuxtLink v-for="(c, i) in data?.categories?.slice(0, 5)" :key="c.id" :to="`/buy${c.name.replace(/\s+/g,'')}`"
        class="flex-shrink-0 border px-6 py-2 font-sans text-label-caps uppercase tracking-[0.1em] transition-colors"
        :class="i === 0 ? 'border-primary bg-primary text-surface' : 'border-outline-variant text-primary hover:border-primary'">
        {{ c.name }}
      </NuxtLink>
    </section>

    <!-- ───────────── LATEST STRATEGIC ASSETS ───────────── -->
    <section class="mx-auto max-w-shell px-margin-mobile py-section-gap md:px-margin-desktop">
      <div class="mb-content-gap flex items-end justify-between">
        <div>
          <p class="eyebrow mb-1">New Acquisitions</p>
          <h2 class="font-display text-headline-md text-primary">Latest Strategic Assets</h2>
        </div>
        <NuxtLink to="/buyHouse" class="font-sans text-label-caps uppercase tracking-[0.1em] text-primary link-underline">View All</NuxtLink>
      </div>
      <div class="grid gap-x-gutter gap-y-content-gap sm:grid-cols-2 lg:grid-cols-3">
        <Reveal v-for="(p, i) in data?.latest" :key="p.id" :delay="(i % 3) * 0.06">
          <PropertyCard :property="p" />
        </Reveal>
      </div>
      <p v-if="!data?.latest?.length" class="font-sans text-body-md text-on-surface-variant">No listings yet.</p>
    </section>

    <!-- ───────────── FEATURED ───────────── -->
    <section v-if="data?.featured?.length" class="border-t border-outline-variant bg-surface-container-low">
      <div class="mx-auto max-w-shell px-margin-mobile py-section-gap md:px-margin-desktop">
        <div class="mb-content-gap flex items-end justify-between">
          <div>
            <p class="eyebrow mb-1">Hand-picked this week</p>
            <h2 class="font-display text-headline-md text-primary">Featured Properties</h2>
          </div>
          <NuxtLink to="/buyHouse" class="font-sans text-label-caps uppercase tracking-[0.1em] text-primary link-underline">View All</NuxtLink>
        </div>
        <div class="grid gap-x-gutter gap-y-content-gap sm:grid-cols-2 lg:grid-cols-3">
          <Reveal v-for="(p, i) in data.featured" :key="p.id" :delay="(i % 3) * 0.06">
            <PropertyCard :property="p" />
          </Reveal>
        </div>
      </div>
    </section>

    <!-- ───────────── EXCLUSIVE (dark, invitation-only) ───────────── -->
    <section v-if="data?.exclusive?.length" class="bg-primary-container">
      <div class="mx-auto max-w-shell px-margin-mobile py-section-gap md:px-margin-desktop">
        <div class="mb-content-gap text-center">
          <h2 class="font-display text-headline-md italic text-surface-container-lowest">Exclusive Invitation-Only</h2>
          <p class="mx-auto mt-2 max-w-md font-sans text-body-md text-on-primary-container">
            Off-market opportunities for significant portfolio diversification.
          </p>
        </div>
        <div class="grid gap-x-gutter gap-y-content-gap md:grid-cols-3">
          <Reveal v-for="(p, i) in data.exclusive" :key="p.id" :delay="i * 0.06">
            <PropertyCard :property="p" dark />
          </Reveal>
        </div>
      </div>
    </section>

    <!-- ───────────── REQUIREMENTS / NEWSLETTER ───────────── -->
    <section class="border-b border-t border-outline-variant">
      <div class="mx-auto max-w-editorial px-margin-mobile py-section-gap text-center md:px-margin-desktop">
        <h2 class="mb-4 font-display text-headline-sm text-primary">तपाईंको सपनाको घर खोज्नुहोस्</h2>
        <p class="mx-auto mb-8 max-w-md font-sans text-body-md text-on-surface-variant">
          Join our inner circle for weekly market reports and exclusive property alerts across Nepal.
        </p>
        <div class="mx-auto flex max-w-md flex-col gap-3">
          <NuxtLink to="/requirements" class="btn-primary">Post a Requirement</NuxtLink>
          <NuxtLink to="/buyHouse" class="btn-outline">Browse All Assets</NuxtLink>
        </div>
      </div>
    </section>

    <!-- ───────────── FAQ ───────────── -->
    <section v-if="data?.faqs?.length" class="mx-auto max-w-editorial px-margin-mobile py-section-gap md:px-margin-desktop">
      <div class="mb-content-gap text-center">
        <p class="eyebrow mb-1">Good to know</p>
        <h2 class="font-display text-headline-md text-primary">Frequently Asked</h2>
      </div>
      <div class="mx-auto max-w-3xl border-t border-outline-variant">
        <div v-for="(f, i) in data.faqs" :key="f.id" class="border-b border-outline-variant">
          <button class="flex w-full items-center justify-between gap-4 py-5 text-left font-display text-headline-sm text-primary"
                  @click="openFaq = openFaq === i ? null : i">
            {{ f.question }}
            <span class="material-symbols-outlined text-secondary transition-transform" :class="openFaq === i ? 'rotate-45' : ''">add</span>
          </button>
          <p v-show="openFaq === i" class="pb-5 font-sans text-body-md text-on-surface-variant">{{ f.answer }}</p>
        </div>
      </div>
    </section>
  </div>
</template>
