<script setup lang="ts">
import type { Category, City, Property, Faq } from '~/types'

const api = useApi()

// Hero: slow cinematic zoom driven by scroll (premium, subtle).
const { y: scrollY } = useWindowScroll()
const heroZoom = computed(() => `scale(${1 + Math.min(scrollY.value, 900) * 0.00025})`)

// Search bar state (functional selects styled as the editorial segments).
const txn = ref<'buy' | 'rent'>('buy')
const searchCategory = ref<string>('')
const searchCity = ref<string>('')

// Server-rendered homepage data (SSR for SEO + fast first paint).
const { data } = await useAsyncData('home', async () => {
  const [featured, exclusive, latest, cats, cities, faqs] = await Promise.all([
    api.get<Property[]>('/properties/featured', { per_page: 3 }),
    api.get<Property[]>('/properties/exclusive', { per_page: 3 }),
    api.get<Property[]>('/properties', { sort: 'newest', per_page: 3 }),
    api.get<Category[]>('/categories'),
    api.get<City[]>('/cities', { popular: 1 }),
    api.get<Faq[]>('/faqs'),
  ])
  return {
    featured: featured.data, exclusive: exclusive.data, latest: latest.data,
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

// Invitation-only rail: real exclusive listings styled as reserved portfolios.
const invites = computed(() => (data.value?.exclusive ?? []).map((p, i) => ({
  ...p,
  ref: `AR-${new Date().getFullYear()}-${String.fromCharCode(88 + i)}${p.id}`,
  dark: i % 3 === 1, // middle card is the black NDA card, like the mockup
})))

useSeoMeta({
  title: 'Premium Real Estate Nepal',
  description:
    "Aakash Realtor — Nepal's premier agency for strategic land acquisition and luxury residential portfolios. Founded on discretion, heritage, and trust.",
  ogTitle: 'Aakash Realtor | Timeless Assets for the Discerning Collector',
})
</script>

<template>
  <div class="-mt-20">
    <!-- ═══════════ HERO (cinematic, centered) ═══════════ -->
    <section class="relative h-[760px] w-full overflow-hidden md:h-[870px]">
      <div class="absolute inset-0 bg-cover bg-center will-change-transform"
           :style="{
             backgroundImage: `url('https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1920&q=80')`,
             transform: heroZoom,
           }">
        <div class="absolute inset-0" style="background: linear-gradient(to bottom, rgba(0,0,0,.3) 0%, rgba(0,0,0,0) 50%, rgba(0,0,0,.4) 100%)" />
      </div>

      <div class="relative flex h-full flex-col items-center justify-end px-margin-mobile pb-24 text-center md:px-margin-desktop">
        <h1 class="mb-8 max-w-4xl animate-fade-up font-display text-display-lg-mobile text-white md:text-display-lg">
          Timeless Assets for the Discerning Collector
        </h1>

        <!-- Structured search bar: Intent / Location / Category / Discover -->
        <div class="flex w-full max-w-4xl animate-fade-up flex-col items-stretch border border-outline-variant bg-surface p-2 shadow-sm md:flex-row" style="animation-delay:.15s">
          <!-- Intent -->
          <label class="group relative flex flex-1 cursor-pointer flex-col justify-center border-b border-outline-variant px-6 py-4 transition-colors hover:bg-surface-container-low md:border-b-0 md:border-r">
            <span class="mb-1 text-left font-sans text-label-caps uppercase text-outline">Intent</span>
            <div class="flex items-center justify-between">
              <span class="font-sans text-body-lg capitalize text-primary">{{ txn }}</span>
              <span class="material-symbols-outlined text-outline">expand_more</span>
            </div>
            <select v-model="txn" class="absolute inset-0 cursor-pointer opacity-0">
              <option value="buy">Buy</option>
              <option value="rent">Rent</option>
            </select>
          </label>
          <!-- Location -->
          <label class="group relative flex flex-[1.4] cursor-pointer flex-col justify-center border-b border-outline-variant px-6 py-4 transition-colors hover:bg-surface-container-low md:border-b-0 md:border-r">
            <span class="mb-1 text-left font-sans text-label-caps uppercase text-outline">Location</span>
            <div class="flex items-center justify-between">
              <span class="truncate font-sans text-body-lg text-primary">
                {{ data?.cities.find(c => String(c.public_id) === searchCity)?.name || 'All of Nepal' }}
              </span>
              <span class="material-symbols-outlined text-outline">location_on</span>
            </div>
            <select v-model="searchCity" class="absolute inset-0 cursor-pointer opacity-0">
              <option value="">All of Nepal</option>
              <option v-for="c in data?.cities" :key="c.id" :value="String(c.public_id)">{{ c.name }}</option>
            </select>
          </label>
          <!-- Category -->
          <label class="group relative flex flex-1 cursor-pointer flex-col justify-center border-b border-outline-variant px-6 py-4 transition-colors hover:bg-surface-container-low md:border-b-0 md:border-r">
            <span class="mb-1 text-left font-sans text-label-caps uppercase text-outline">Category</span>
            <div class="flex items-center justify-between">
              <span class="truncate font-sans text-body-lg text-primary">
                {{ data?.categories.find(c => c.slug === searchCategory)?.name || 'Any' }}
              </span>
              <span class="material-symbols-outlined text-outline">domain</span>
            </div>
            <select v-model="searchCategory" class="absolute inset-0 cursor-pointer opacity-0">
              <option value="">Any</option>
              <option v-for="c in data?.categories" :key="c.id" :value="c.slug">{{ c.name }}</option>
            </select>
          </label>
          <!-- Discover -->
          <button class="bg-primary px-10 py-4 font-sans text-label-caps uppercase tracking-widest text-white transition-colors hover:bg-on-surface-variant active:scale-95" @click="runSearch">
            Discover
          </button>
        </div>
      </div>
    </section>

    <!-- ═══════════ LATEST STRATEGIC ASSETS ═══════════ -->
    <section class="mx-auto max-w-shell px-margin-mobile py-section-gap md:px-margin-desktop">
      <Reveal class="mb-12 flex items-end justify-between border-b border-outline-variant pb-6">
        <div>
          <span class="mb-2 block font-sans text-label-caps uppercase tracking-widest text-secondary">Curated Collection</span>
          <h2 class="font-display text-headline-md text-primary">Latest Strategic Assets</h2>
        </div>
        <NuxtLink to="/buyHouse" class="border-b border-primary pb-1 font-sans text-label-caps uppercase tracking-[0.1em] text-primary transition-all hover:border-secondary hover:text-secondary">
          View All Listings
        </NuxtLink>
      </Reveal>
      <div class="grid grid-cols-1 gap-gutter md:grid-cols-3">
        <Reveal v-for="(p, i) in data?.latest" :key="p.id" :delay="i * 0.08">
          <PropertyCard :property="p" />
        </Reveal>
      </div>
      <p v-if="!data?.latest?.length" class="font-sans text-body-md text-on-surface-variant">No listings yet.</p>
    </section>

    <!-- ═══════════ FEATURED ═══════════ -->
    <section v-if="data?.featured?.length" class="mx-auto max-w-shell px-margin-mobile pb-section-gap md:px-margin-desktop">
      <Reveal class="mb-12 flex items-end justify-between border-b border-outline-variant pb-6">
        <div>
          <span class="mb-2 block font-sans text-label-caps uppercase tracking-widest text-secondary">Hand-Picked</span>
          <h2 class="font-display text-headline-md text-primary">Featured Properties</h2>
        </div>
        <NuxtLink to="/buyHouse" class="border-b border-primary pb-1 font-sans text-label-caps uppercase tracking-[0.1em] text-primary transition-all hover:border-secondary hover:text-secondary">
          View All
        </NuxtLink>
      </Reveal>
      <div class="grid grid-cols-1 gap-gutter md:grid-cols-3">
        <Reveal v-for="(p, i) in data.featured" :key="p.id" :delay="i * 0.08">
          <PropertyCard :property="p" />
        </Reveal>
      </div>
    </section>

    <!-- ═══════════ EXCLUSIVE INVITATION-ONLY (horizontal rail) ═══════════ -->
    <section v-if="invites.length" class="bg-surface-container-highest py-section-gap">
      <Reveal class="mb-12 px-margin-mobile md:px-margin-desktop">
        <span class="mb-2 block font-sans text-label-caps uppercase tracking-widest text-secondary">Reserved Portfolios</span>
        <h2 class="font-display text-headline-md text-primary">Exclusive Invitation-Only</h2>
        <p class="mt-4 max-w-xl font-sans text-body-md text-on-surface-variant">
          Off-market opportunities for institutional investors and family offices. Access to
          Nepal's most significant land holdings and heritage assets.
        </p>
      </Reveal>
      <div class="no-scrollbar flex gap-gutter overflow-x-auto px-margin-mobile pb-8 md:px-margin-desktop">
        <NuxtLink v-for="p in invites" :key="p.id" :to="p.url"
          class="flex aspect-[3/4] min-w-[320px] flex-col justify-between p-10 md:min-w-[450px] md:p-12"
          :class="p.dark ? 'bg-primary text-white' : 'border border-outline-variant bg-surface'">
          <div>
            <span class="mb-10 inline-block border px-3 py-1 font-sans text-label-caps"
                  :class="p.dark ? 'border-primary-fixed-dim text-primary-fixed-dim' : 'border-outline text-outline'">
              Ref: {{ p.ref }}
            </span>
            <h3 class="font-display text-headline-md leading-snug" :class="p.dark ? 'text-white' : 'text-primary'">
              {{ p.title }}
            </h3>
            <p class="mt-6 font-sans text-body-md italic" :class="p.dark ? 'text-primary-fixed-dim' : 'text-on-surface-variant'">
              {{ p.location?.city?.name }}{{ p.location?.area?.name ? ` · ${p.location.area.name}` : '' }}
              — {{ p.price.formatted.replace(/^Rs\.?/i, 'NPR') }}
            </p>
          </div>
          <div class="group mt-auto flex items-center gap-4">
            <span class="font-sans text-label-caps uppercase tracking-widest">{{ p.dark ? 'NDA Required' : 'Request Access' }}</span>
            <span class="material-symbols-outlined transition-transform group-hover:translate-x-2">{{ p.dark ? 'lock' : 'arrow_forward' }}</span>
          </div>
        </NuxtLink>
      </div>
    </section>

    <!-- ═══════════ POST A REQUIREMENT CTA (split panel) ═══════════ -->
    <section class="flex justify-center px-margin-mobile py-section-gap md:px-margin-desktop">
      <Reveal class="flex w-full max-w-5xl flex-col border border-outline-variant md:flex-row">
        <div class="flex-1 bg-surface p-12 md:p-20">
          <h2 class="mb-6 font-display text-headline-md text-primary">Can't Find What You're Seeking?</h2>
          <p class="mb-10 font-sans text-body-lg text-on-surface-variant">
            Our acquisition team specializes in sourcing bespoke assets that meet specific
            technical and geographical requirements. Let us conduct the search for you.
          </p>
          <NuxtLink to="/requirements"
            class="group inline-flex items-center gap-4 bg-primary px-12 py-5 font-sans text-label-caps uppercase tracking-widest text-white transition-colors hover:bg-on-surface-variant">
            Post a Requirement
            <span class="material-symbols-outlined text-[18px] transition-transform group-hover:translate-x-1">send</span>
          </NuxtLink>
        </div>
        <div class="hidden w-1/3 bg-cover bg-center md:block"
             style="background-image: url('https://images.unsplash.com/photo-1497366216548-37526070297c?w=800&q=80')" />
      </Reveal>
    </section>

    <!-- ═══════════ FAQ ═══════════ -->
    <section v-if="data?.faqs?.length" class="mx-auto max-w-editorial px-margin-mobile pb-section-gap md:px-margin-desktop">
      <Reveal class="mb-12 border-b border-outline-variant pb-6">
        <span class="mb-2 block font-sans text-label-caps uppercase tracking-widest text-secondary">Good to Know</span>
        <h2 class="font-display text-headline-md text-primary">Frequently Asked</h2>
      </Reveal>
      <div class="border-t border-outline-variant">
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

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
