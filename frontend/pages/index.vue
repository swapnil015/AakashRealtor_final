<script setup lang="ts">
import type { Category, City, Property, Faq } from '~/types'

const api = useApi()

// Hero scroll parallax (image drifts slower than the page).
const { y: scrollY } = useWindowScroll()
const heroParallax = computed(() => `translateY(${scrollY.value * 0.16}px)`)

// Search bar state.
const txn = ref<'buy' | 'rent'>('buy')
const searchCategory = ref<string>('')
const searchCity = ref<string>('')

// Server-rendered homepage data (SSR for SEO + fast first paint).
const { data } = await useAsyncData('home', async () => {
  const [featured, exclusive, latest, emerging, byOwner, cats, cities, faqs] = await Promise.all([
    api.get<Property[]>('/properties/featured', { per_page: 4 }),
    api.get<Property[]>('/properties/exclusive', { per_page: 3 }),
    api.get<Property[]>('/properties', { sort: 'newest', per_page: 8 }),
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
  title: 'Find a home that lives up to you',
  description:
    "Aakash Realtor — Nepal's curated property marketplace. Hand-verified villas, apartments and land across the Kathmandu valley, every listing checked against its lalpurja.",
  ogTitle: 'Aakash Realtor — Nepal Property Marketplace',
})
</script>

<template>
  <div>
    <!-- ───────────── HERO ───────────── -->
    <section class="relative flex min-h-[92vh] items-center overflow-hidden">
      <div class="absolute -inset-[8%] bg-ink will-change-transform" :style="{ transform: heroParallax }">
        <img src="https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1920&q=80"
             alt="" class="kenburns h-full w-full object-cover opacity-70" />
      </div>
      <div class="absolute inset-0 bg-gradient-to-r from-ink/90 via-ink/60 to-ink/20" />

      <div class="container-px relative w-full pt-24">
        <div class="max-w-2xl text-white">
          <p class="eyebrow animate-fade-up">Nepal's Curated Property Collection</p>
          <h1 class="mt-5 font-display text-5xl font-semibold leading-[1.02] animate-fade-up sm:text-7xl"
              style="animation-delay:.1s">
            Find a home that<br />lives up to you.
          </h1>
          <p class="mt-5 max-w-lg text-lg leading-relaxed text-white/80 animate-fade-up" style="animation-delay:.2s">
            Hand-verified villas, apartments and land across the Kathmandu valley — every listing
            checked against its lalpurja.
          </p>

          <!-- Search bar -->
          <div class="mt-8 animate-fade-up rounded-2xl bg-white/95 p-3 shadow-lift backdrop-blur sm:p-4"
               style="animation-delay:.3s">
            <div class="mb-3 inline-flex rounded-lg bg-sand p-1">
              <button v-for="t in (['buy','rent'] as const)" :key="t"
                class="rounded-md px-5 py-2 text-sm font-bold capitalize transition"
                :class="txn === t ? 'bg-gold text-ink' : 'text-muted'"
                @click="txn = t">{{ t }}</button>
            </div>
            <div class="grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
              <select v-model="searchCategory" class="field text-ink">
                <option value="">Property type</option>
                <option v-for="c in data?.categories" :key="c.id" :value="c.slug">{{ c.name }}</option>
              </select>
              <select v-model="searchCity" class="field text-ink">
                <option value="">All cities</option>
                <option v-for="c in data?.cities" :key="c.id" :value="String(c.public_id)">{{ c.name }}</option>
              </select>
              <button class="btn-gold whitespace-nowrap" @click="runSearch">Search</button>
            </div>
          </div>

          <!-- Stats (count up when they enter view) -->
          <div class="mt-10 flex gap-10 animate-fade-up" style="animation-delay:.4s">
            <div v-for="s in [
              { to: 2800, suffix: '+', label: 'Verified listings' },
              { to: 18, suffix: ' yrs', label: 'In the valley' },
              { to: 100, suffix: '%', label: 'Lalpurja checked' },
            ]" :key="s.label">
              <div v-count="{ to: s.to, suffix: s.suffix }" class="font-display text-3xl font-semibold">{{ s.to }}{{ s.suffix }}</div>
              <div class="mt-1 text-[13px] text-white/60">{{ s.label }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Floating scroll cue -->
      <div class="absolute bottom-7 left-1/2 flex -translate-x-1/2 flex-col items-center gap-2 text-white/70">
        <span class="text-[10px] font-semibold uppercase tracking-[0.2em]">Scroll</span>
        <span class="animate-float text-lg">↓</span>
      </div>
    </section>

    <!-- ───────────── FEATURED ───────────── -->
    <section class="container-px py-24">
      <Reveal class="mb-12 flex items-end justify-between">
        <div>
          <p class="eyebrow">Hand-picked this week</p>
          <h2 class="mt-3 font-display text-5xl font-semibold tracking-tight">Featured Properties</h2>
        </div>
        <NuxtLink to="/buyHouse" class="hidden border-b-2 border-gold pb-1 text-sm font-semibold sm:block">
          View all featured →
        </NuxtLink>
      </Reveal>
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <Reveal v-for="(p, i) in data?.featured" :key="p.id" :delay="i * 0.08">
          <PropertyCard :property="p" />
        </Reveal>
      </div>
      <p v-if="!data?.featured?.length" class="text-muted">No featured properties yet.</p>
    </section>

    <!-- ───────────── EXCLUSIVE (dark) ───────────── -->
    <section v-if="data?.exclusive?.length" class="bg-ink py-24 text-white">
      <div class="container-px">
        <Reveal class="mb-12 text-center">
          <p class="eyebrow">By invitation</p>
          <h2 class="mt-3 font-display text-5xl font-semibold tracking-tight text-white">The Exclusive Collection</h2>
        </Reveal>
        <div class="grid gap-6 md:grid-cols-3">
          <Reveal v-for="(p, i) in data.exclusive" :key="p.id" :delay="i * 0.08">
            <PropertyCard :property="p" dark />
          </Reveal>
        </div>
      </div>
    </section>

    <!-- ───────────── LATEST ───────────── -->
    <section class="container-px py-24">
      <Reveal class="mb-12 text-center">
        <p class="eyebrow">Fresh on the market</p>
        <h2 class="mt-3 font-display text-5xl font-semibold tracking-tight">Latest Listings</h2>
      </Reveal>
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <Reveal v-for="(p, i) in data?.latest" :key="p.id" :delay="(i % 4) * 0.06">
          <PropertyCard :property="p" />
        </Reveal>
      </div>
    </section>

    <!-- ───────────── REQUIREMENTS TEASER ───────────── -->
    <section class="container-px pb-24">
      <Reveal class="overflow-hidden rounded-3xl bg-gradient-to-br from-ink to-ink-soft p-12 text-white sm:p-16">
        <div class="max-w-xl">
          <p class="eyebrow">Didn't find it?</p>
          <h2 class="mt-3 font-display text-4xl font-semibold sm:text-5xl">Tell us what you're looking for.</h2>
          <p class="mt-4 text-white/75">
            Post your requirement and we'll alert you the moment a matching, lalpurja-verified
            property comes to market.
          </p>
          <NuxtLink to="/requirements" class="btn-gold mt-7">Post a Requirement</NuxtLink>
        </div>
      </Reveal>
    </section>

    <!-- ───────────── FAQ ───────────── -->
    <section v-if="data?.faqs?.length" class="container-px pb-24">
      <Reveal class="mx-auto max-w-3xl">
        <p class="eyebrow text-center">Good to know</p>
        <h2 class="mt-3 text-center font-display text-5xl font-semibold">Frequently Asked</h2>
        <div class="mt-10 divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white">
          <div v-for="(f, i) in data.faqs" :key="f.id" class="px-6">
            <button class="flex w-full items-center justify-between py-5 text-left font-semibold"
                    @click="openFaq = openFaq === i ? null : i">
              {{ f.question }}
              <span class="text-gold transition" :class="openFaq === i ? 'rotate-45' : ''">+</span>
            </button>
            <p v-show="openFaq === i" class="pb-5 text-muted">{{ f.answer }}</p>
          </div>
        </div>
      </Reveal>
    </section>
  </div>
</template>
