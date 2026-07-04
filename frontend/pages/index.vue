<script setup lang="ts">
import type { Category, City, Property } from '~/types'

const api = useApi()

/* ── Server-rendered homepage data (SSR for SEO + fast first paint) ── */
const { data } = await useAsyncData('home', async () => {
  const [featured, exclusive, latest, cats, cities, team, blogs] = await Promise.all([
    api.get<Property[]>('/properties/featured', { per_page: 6 }),
    api.get<Property[]>('/properties/exclusive', { per_page: 4 }),
    api.get<Property[]>('/properties', { sort: 'newest', per_page: 6 }),
    api.get<Category[]>('/categories'),
    api.get<City[]>('/cities', { popular: 1 }),
    api.get<any[]>('/team'),
    api.get<any[]>('/blogs', { per_page: 3 }),
  ])
  return {
    featured: featured.data, exclusive: exclusive.data, latest: latest.data,
    categories: cats.data, cities: cities.data, team: team.data, blogs: blogs.data,
  }
})

/* ── Floating search bar ── */
const txn = ref<'buy' | 'rent'>('buy')
const q = ref('')
const searchCategory = ref('')
const searchBudget = ref('')

const budgets = [
  { label: 'Under 50 Lakh', value: '-5000000' },
  { label: '50 Lakh – 1 Cr', value: '5000000-10000000' },
  { label: '1 – 3 Cr', value: '10000000-30000000' },
  { label: '3 Cr +', value: '30000000-' },
]

function runSearch() {
  const cat = data.value?.categories.find((c) => c.slug === searchCategory.value)
  const seg = `${txn.value}${(cat?.name || 'House').replace(/\s+/g, '')}`
  const [min, max] = (searchBudget.value || '-').split('-')
  navigateTo({
    path: `/${seg}`,
    query: {
      ...(q.value ? { q: q.value } : {}),
      ...(min ? { min_price: min } : {}),
      ...(max ? { max_price: max } : {}),
    },
  })
}

/* ── Featured bento: 2 hero tiles + 4 compact tiles ── */
const bento = computed(() => {
  const pool = [...(data.value?.featured ?? [])]
  for (const p of data.value?.latest ?? []) {
    if (pool.length >= 6) break
    if (!pool.some((x) => x.id === p.id)) pool.push(p)
  }
  return pool.slice(0, 6).map((p, i) => ({ ...p, big: i < 2 }))
})

/* ── Flagship horizontal-scroll rail (pinned) ── */
const flagship = computed(() => {
  const ex = data.value?.exclusive ?? []
  return (ex.length ? ex : bento.value).slice(0, 4)
})

const hwrap = ref<HTMLElement | null>(null)
const htrack = ref<HTMLElement | null>(null)

/* ── Trust stats count-up ── */
const stats = [
  { value: 500, suffix: '+', label: 'Properties' },
  { value: 120, suffix: '+', label: 'Verified Agents' },
  { value: 1000, suffix: '+', label: 'Happy Buyers' },
  { value: 15, suffix: '+', label: 'Years in Nepal' },
]
const statEl = ref<HTMLElement | null>(null)
const counts = ref(stats.map(() => 0))

/* ── Testimonials crossfade ── */
const testimonials = [
  { quote: 'They handled the lalpurja transfer, the survey, everything. We flew in from Sydney, signed, and it was done.', name: 'Ramesh & Priya Shrestha', detail: 'Bought in Budhanilkantha · 2025' },
  { quote: 'The only agency that told us not to buy a plot — the road access wasn’t registered. That honesty won us over.', name: 'Deepak Karki', detail: 'Land investor, Chandragiri' },
  { quote: 'Our Lakeside villa sold in three weeks, above asking. The photography and buyer vetting were world-class.', name: 'Maya Gurung', detail: 'Sold in Pokhara · 2026' },
]
const tIndex = ref(0)
let tTimer: ReturnType<typeof setInterval> | null = null

/* ── Collections (browse by category, live counts) ── */
const collectionImages: Record<string, string> = {
  house: 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80',
  land: 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1200&q=80',
  apartment: 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1200&q=80',
  flat: 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1200&q=80',
  commercial: 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80',
  residential: 'https://images.unsplash.com/photo-1570129477492-45c003edd2be?auto=format&fit=crop&w=1200&q=80',
}
const collections = computed(() => (data.value?.categories ?? []).map((c, i) => ({
  ...c,
  img: collectionImages[c.slug] || collectionImages.house,
  span: i === 0,
  to: `/buy${c.name.replace(/\s+/g, '')}`,
})))

/* ── Contact / requirement form ── */
const form = reactive({
  name: '', phone: '', transaction_type: 'buy',
  budget: '', category_id: '', city_id: '', message: '',
})
const formState = ref<'idle' | 'sending' | 'sent' | 'error'>('idle')
const formError = ref('')

async function submitRequirement() {
  if (formState.value === 'sending') return
  formState.value = 'sending'
  formError.value = ''
  const [min, max] = (form.budget || '-').split('-')
  try {
    await api.post('/requirements', {
      name: form.name,
      phone: form.phone,
      transaction_type: form.transaction_type,
      category_id: Number(form.category_id) || data.value?.categories[0]?.id,
      city_id: Number(form.city_id) || data.value?.cities[0]?.id,
      ...(min ? { min_budget: Number(min) } : {}),
      ...(max ? { max_budget: Number(max) } : {}),
      message: form.message || undefined,
    })
    formState.value = 'sent'
  } catch (e: any) {
    formState.value = 'error'
    formError.value = e?.message || 'Something went wrong — please try again.'
  }
}

/* ── Scroll effects: pinned horizontal rail + stat count-up ── */
function onScroll() {
  const wrap = hwrap.value
  const track = htrack.value
  if (wrap && track && window.innerWidth > 900) {
    const vh = window.innerHeight
    const total = wrap.offsetHeight - vh
    const p = Math.min(1, Math.max(0, (window.scrollY - wrap.offsetTop) / total))
    const dist = track.scrollWidth - window.innerWidth
    track.style.transform = `translateX(${(-p * dist).toFixed(1)}px)`
  }
}

function startCountUp() {
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  stats.forEach((s, i) => {
    if (reduced) { counts.value[i] = s.value; return }
    const t0 = performance.now()
    const dur = 1600
    const step = (t: number) => {
      const p = Math.min(1, (t - t0) / dur)
      counts.value[i] = Math.round(s.value * (1 - Math.pow(1 - p, 4)))
      if (p < 1) requestAnimationFrame(step)
    }
    requestAnimationFrame(step)
  })
}

onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true })
  onScroll()

  if (statEl.value) {
    const io = new IntersectionObserver((entries) => {
      if (entries.some((e) => e.isIntersecting)) {
        startCountUp()
        io.disconnect()
      }
    }, { threshold: 0.3 })
    io.observe(statEl.value)
  }

  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    tTimer = setInterval(() => { tIndex.value = (tIndex.value + 1) % testimonials.length }, 5000)
  }
})
onUnmounted(() => {
  window.removeEventListener('scroll', onScroll)
  if (tTimer) clearInterval(tTimer)
})

function fmtPrice(p: Property) {
  return (p.price?.formatted || '').replace(/^Rs\.?\s?/i, 'NPR ')
}
function fmtLocation(p: Property) {
  return [p.location?.area?.name, p.location?.city?.name].filter(Boolean).join(', ') || 'Nepal'
}
function fmtSpecs(p: Property) {
  const s = p.specs
  if (s?.bedrooms) return `${s.bedrooms} bed · ${s.bathrooms ?? '—'} bath`
  return p.area?.size ? `${p.area.size} ${p.area.unit}` : ''
}
function imgOf(p: Property) {
  return p.primary_image || p.images?.[0]?.sizes?.medium || p.images?.[0]?.url || ''
}
function blogDate(d: string | null) {
  return d ? new Date(d).toLocaleDateString('en-GB', { month: 'short', year: 'numeric' }) : ''
}

useSeoMeta({
  title: 'Find Your Next Property with Confidence',
  description:
    'Aakash Realtor — Nepal’s premium marketplace for land, homes and investment properties across Kathmandu, Lalitpur and Pokhara. We walk every plot and read every lalpurja.',
  ogTitle: 'Aakash Realtor | Find your next property with confidence',
})
</script>

<template>
  <div class="-mt-20 bg-surface">
    <!-- ═══════════ HERO ═══════════ -->
    <section class="relative flex h-screen min-h-[640px] flex-col justify-center overflow-hidden bg-navy">
      <div class="absolute inset-0 animate-fade" style="animation-delay:.3s">
        <img src="https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=2000&q=80"
             alt="Luxury residence at dusk"
             class="h-full w-full animate-kenburns object-cover will-change-transform" style="animation-delay:.3s" />
      </div>
      <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(11,19,43,0.72) 0%, rgba(11,19,43,0.35) 45%, rgba(11,19,43,0.82) 100%)" />

      <div class="relative z-[2] mx-auto w-full max-w-shell px-margin-mobile md:px-margin-desktop">
        <div class="overflow-hidden">
          <div class="mb-7 animate-rise text-[12px] font-medium uppercase tracking-[0.32em] text-secondary" style="animation-delay:.5s">
            Kathmandu · Lalitpur · Pokhara — est. 2011
          </div>
        </div>
        <h1 class="max-w-[15ch] font-display text-[clamp(3rem,7vw,7rem)] font-medium leading-[1.06] text-surface">
          <span class="block overflow-hidden"><span class="block animate-rise" style="animation-delay:.7s">Find your next property</span></span>
          <span class="block overflow-hidden">
            <span class="block animate-rise" style="animation-delay:.85s">
              with <em class="relative inline-block italic">confidence<svg viewBox="0 0 220 14" class="absolute -bottom-[0.18em] left-[-2%] h-[0.22em] w-[104%] overflow-visible" preserveAspectRatio="none"><path d="M4 10 C 60 3, 150 2, 216 7" fill="none" stroke="#C7A76C" stroke-width="4" stroke-linecap="round" opacity="0.85" /></svg></em>
            </span>
          </span>
        </h1>
        <p class="mt-8 max-w-[480px] animate-fade-up font-sans text-[18px] leading-[1.6] text-surface/75" style="animation-delay:1.1s">
          We walk every plot, read every lalpurja, and tell you when <em>not</em> to buy.
          Land, homes &amp; investment property across Nepal.
        </p>
        <div class="mt-11 flex flex-wrap gap-4 animate-fade-up" style="animation-delay:1.3s">
          <NuxtLink to="/buyHouse"
            class="rounded-full bg-secondary px-9 py-[18px] font-sans text-[13px] font-semibold uppercase tracking-[0.1em] text-navy transition-all duration-300 ease-smooth hover:-translate-y-0.5 hover:bg-secondary-fixed-dim hover:shadow-gold">
            Browse Properties
          </NuxtLink>
          <NuxtLink to="/post"
            class="rounded-full border border-surface/40 px-9 py-[18px] font-sans text-[13px] font-medium uppercase tracking-[0.1em] text-surface transition-all duration-300 ease-smooth hover:border-secondary hover:text-secondary">
            List Property
          </NuxtLink>
        </div>
      </div>
    </section>

    <!-- ═══════════ FLOATING SEARCH BAR ═══════════ -->
    <section class="relative z-10 mx-auto -mt-[72px] w-full max-w-[1120px] px-margin-mobile md:px-margin-desktop">
      <div class="animate-fade-up rounded-2xl border border-white/60 bg-surface/90 p-7 shadow-search backdrop-blur-xl md:p-8" style="animation-delay:1.6s">
        <div class="mb-5 flex gap-2">
          <button v-for="mode in (['buy', 'rent'] as const)" :key="mode"
            class="rounded-full px-6 py-2.5 font-sans text-[12px] font-semibold uppercase tracking-[0.1em] transition-all duration-300 ease-smooth"
            :class="txn === mode ? 'bg-navy text-surface' : 'bg-transparent text-on-surface/60 hover:text-on-surface'"
            @click="txn = mode">
            {{ mode }}
          </button>
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-[1.3fr_1fr_1fr_auto]">
          <input v-model="q" placeholder="Try 'Budhanilkantha' or 'Lakeside'…" class="input-line" @keyup.enter="runSearch" />
          <select v-model="searchCategory" class="input-line cursor-pointer">
            <option value="">Property type</option>
            <option v-for="c in data?.categories" :key="c.id" :value="c.slug">{{ c.name }}</option>
          </select>
          <select v-model="searchBudget" class="input-line cursor-pointer">
            <option value="">Budget</option>
            <option v-for="b in budgets" :key="b.value" :value="b.value">{{ b.label }}</option>
          </select>
          <button
            class="rounded-lg bg-navy px-8 py-4 font-sans text-[13px] font-semibold uppercase tracking-[0.08em] text-surface transition-all duration-300 ease-smooth hover:-translate-y-0.5 hover:bg-primary-container hover:shadow-lift"
            @click="runSearch">
            Search
          </button>
        </div>
      </div>
    </section>

    <!-- ═══════════ TRUST STATS ═══════════ -->
    <section ref="statEl" class="mx-auto max-w-shell px-margin-mobile pb-section-gap pt-[140px] md:px-margin-desktop">
      <Reveal>
        <p class="max-w-[30ch] font-display text-[clamp(1.5rem,2.6vw,2.3rem)] leading-[1.45] text-navy">
          Fifteen years of walking plots, checking titles, and saying <em>no</em> more often than yes.
        </p>
        <div class="mt-14 grid grid-cols-2 gap-8 border-t border-on-surface/10 pt-9 md:grid-cols-4">
          <div v-for="(s, i) in stats" :key="s.label">
            <div class="font-display text-[clamp(2.4rem,3.6vw,3.6rem)] font-medium text-navy">
              {{ counts[i].toLocaleString() }}<span class="text-secondary">{{ s.suffix }}</span>
            </div>
            <div class="mt-2.5 text-[12px] uppercase tracking-[0.22em] text-on-surface/50">{{ s.label }}</div>
          </div>
        </div>
        <div class="mt-5 font-sans text-[12px] italic text-on-surface/40">* figures as of Asar 2083 — we update them quarterly, honestly.</div>
      </Reveal>
    </section>

    <!-- ═══════════ FEATURED BENTO ═══════════ -->
    <section class="mx-auto max-w-shell px-margin-mobile pb-section-gap md:px-margin-desktop">
      <Reveal class="mb-12 flex flex-wrap items-end justify-between gap-6">
        <div>
          <div class="mb-4 text-[12px] font-medium uppercase tracking-[0.28em] text-secondary">01 — Curated, not scraped</div>
          <h2 class="font-display text-[clamp(2rem,4vw,4rem)] font-medium leading-[1.1] text-navy">Six we'd buy ourselves</h2>
        </div>
        <NuxtLink to="/buyHouse" class="border-b-[1.5px] border-secondary pb-1.5 font-sans text-[13px] font-medium uppercase tracking-[0.1em] text-navy transition-colors hover:text-secondary">
          View all properties
        </NuxtLink>
      </Reveal>

      <div class="grid auto-rows-[220px] grid-cols-2 gap-6 md:grid-cols-4">
        <Reveal v-for="(p, i) in bento" :key="p.id" :delay="i * 0.08"
          :class="p.big ? 'col-span-2 row-span-2' : 'col-span-1 row-span-1'">
          <NuxtLink :to="p.url"
            class="card-tile group relative block h-full w-full overflow-hidden rounded-xl transition-all duration-500 ease-smooth hover:-translate-y-1.5 hover:shadow-card">
            <img :src="imgOf(p)" :alt="p.title" loading="lazy"
                 class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 ease-smooth group-hover:scale-[1.06]" />
            <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(11,19,43,0) 30%, rgba(11,19,43,0.85) 100%)" />
            <div v-if="p.flags?.is_featured || p.flags?.is_exclusive"
                 class="absolute left-4 top-4 flex items-center gap-1.5 rounded-full bg-forest/90 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-surface">
              ✓ Verified
            </div>
            <div v-if="i === 0"
                 class="absolute right-4 top-[18px] -rotate-[2.5deg] rounded-[3px] bg-peach px-4 py-1.5 font-display text-[15px] italic text-navy shadow-[0_4px_14px_rgba(11,19,43,0.3)]">
              the founder's pick
            </div>
            <div class="absolute inset-x-0 bottom-0" :class="p.big ? 'p-[22px]' : 'p-4'">
              <div class="mb-1.5 truncate text-[10px] uppercase tracking-[0.16em] text-surface/70">{{ fmtLocation(p) }}</div>
              <div class="line-clamp-2 font-display leading-[1.2] text-surface" :class="p.big ? 'text-[28px]' : 'text-[16px]'">{{ p.title }}</div>
              <div class="mt-2 flex items-center justify-between gap-2.5">
                <div class="whitespace-nowrap text-[14px] font-semibold text-secondary transition-all group-hover:[text-shadow:0_0_18px_rgba(199,167,108,0.6)]">{{ fmtPrice(p) }}</div>
                <div v-if="p.big" class="whitespace-nowrap text-[12px] text-surface/65">{{ fmtSpecs(p) }}</div>
              </div>
              <div v-if="p.big" class="mt-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-surface opacity-0 transition-all duration-300 ease-smooth group-hover:opacity-100">
                View Property →
              </div>
            </div>
          </NuxtLink>
        </Reveal>
      </div>
    </section>

    <!-- ═══════════ FLAGSHIP HORIZONTAL SCROLL ═══════════ -->
    <section v-if="flagship.length" ref="hwrap" class="relative bg-navy md:h-[400vh]">
      <div class="md:sticky md:top-0 md:h-screen md:overflow-hidden">
        <div ref="htrack" class="flex flex-col will-change-transform md:h-full md:flex-row">
          <div v-for="(f, i) in flagship" :key="f.id"
               class="flex w-full flex-none flex-col items-stretch md:h-full md:w-screen md:flex-row">
            <div class="relative min-h-[280px] overflow-hidden md:flex-[1.15]">
              <img :src="imgOf(f)" :alt="f.title" loading="lazy" class="absolute inset-0 h-full w-full object-cover" />
              <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(11,19,43,0) 60%, rgba(11,19,43,0.6) 100%)" />
            </div>
            <div class="flex flex-col justify-center p-8 text-surface md:flex-1 md:p-16">
              <div class="font-display text-[64px] font-normal leading-none text-secondary/35 md:text-[84px]">0{{ i + 1 }}</div>
              <div class="mb-4 mt-6 text-[11px] uppercase tracking-[0.28em] text-secondary">{{ fmtLocation(f) }}</div>
              <div class="font-display text-[clamp(1.8rem,3vw,3rem)] leading-[1.15]">{{ f.title }}</div>
              <p class="mt-5 max-w-[42ch] font-sans text-[16px] leading-[1.7] text-surface/65">
                {{ f.description?.slice(0, 220) }}{{ (f.description?.length ?? 0) > 220 ? '…' : '' }}
              </p>
              <div class="my-8 flex flex-wrap gap-8">
                <div>
                  <div class="text-[18px] font-semibold text-surface">{{ f.area?.size ? `${f.area.size} ${f.area.unit}` : '—' }}</div>
                  <div class="mt-1 text-[11px] uppercase tracking-[0.16em] text-surface/45">Area</div>
                </div>
                <div>
                  <div class="text-[18px] font-semibold text-surface">{{ f.specs?.facing || '—' }}</div>
                  <div class="mt-1 text-[11px] uppercase tracking-[0.16em] text-surface/45">Facing</div>
                </div>
                <div>
                  <div class="text-[18px] font-semibold text-secondary">{{ fmtPrice(f) }}</div>
                  <div class="mt-1 text-[11px] uppercase tracking-[0.16em] text-surface/45">Price</div>
                </div>
              </div>
              <NuxtLink :to="f.url"
                class="self-start rounded-full bg-secondary px-8 py-4 font-sans text-[12px] font-semibold uppercase tracking-[0.14em] text-navy transition-all duration-300 ease-smooth hover:-translate-y-0.5 hover:bg-secondary-fixed-dim">
                Explore this property
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══════════ COLLECTIONS ═══════════ -->
    <section class="mx-auto max-w-shell px-margin-mobile py-section-gap md:px-margin-desktop">
      <Reveal class="mb-12 flex flex-col items-end text-right">
        <div class="mb-4 text-[12px] font-medium uppercase tracking-[0.28em] text-secondary">02 — Browse by intent</div>
        <h2 class="font-display text-[clamp(2rem,4vw,4rem)] font-medium leading-[1.1] text-navy">What are you <em>really</em> looking for?</h2>
      </Reveal>
      <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <Reveal v-for="(c, i) in collections" :key="c.id" :delay="i * 0.06" :class="c.span ? 'md:col-span-2' : ''">
          <NuxtLink :to="c.to"
            class="group relative block h-[300px] overflow-hidden rounded-xl transition-all duration-500 ease-smooth hover:-translate-y-1.5 hover:shadow-card">
            <img :src="c.img" :alt="c.name" loading="lazy"
                 class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 ease-smooth group-hover:scale-[1.06]" />
            <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(11,19,43,0.1) 40%, rgba(11,19,43,0.78) 100%)" />
            <div class="absolute inset-x-0 bottom-0 flex items-end justify-between p-6">
              <div>
                <div class="font-display text-[26px] text-surface">{{ c.name }}</div>
                <div class="mt-1 text-[12px] text-surface/65">{{ c.properties_count ?? 0 }} listings</div>
              </div>
              <div class="text-[18px] text-secondary opacity-0 transition-all duration-300 group-hover:opacity-100">→</div>
            </div>
          </NuxtLink>
        </Reveal>
      </div>
    </section>

    <!-- ═══════════ VERIFIED AGENTS ═══════════ -->
    <section v-if="data?.team?.length" class="bg-peach">
      <div class="mx-auto max-w-shell px-margin-mobile py-[130px] md:px-margin-desktop">
        <Reveal class="mb-12 flex flex-wrap items-end justify-between gap-8">
          <div>
            <div class="mb-4 text-[12px] font-medium uppercase tracking-[0.28em] text-secondary">03 — The people behind the keys</div>
            <h2 class="font-display text-[clamp(2rem,4vw,4rem)] font-medium leading-[1.1] text-navy">Verified agents</h2>
          </div>
          <p class="mb-1.5 max-w-[34ch] font-sans text-[15px] italic leading-[1.6] text-on-surface/55">
            Every agent here has done at least a hundred site visits. Yes, we count.
          </p>
        </Reveal>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-4">
          <Reveal v-for="(a, i) in data.team.slice(0, 4)" :key="a.id" :delay="i * 0.1">
            <div class="group cursor-pointer overflow-hidden rounded-xl bg-surface transition-all duration-500 ease-smooth hover:-translate-y-1.5 hover:shadow-card">
              <div class="h-60 overflow-hidden">
                <img :src="a.photo" :alt="a.name" loading="lazy"
                     class="h-full w-full object-cover transition-transform duration-700 ease-smooth group-hover:scale-[1.06]" />
              </div>
              <div class="px-[22px] pb-6 pt-5">
                <div class="flex items-center gap-2">
                  <div class="font-display text-[19px] text-navy">{{ a.name }}</div>
                  <div class="flex h-4 w-4 items-center justify-center rounded-full bg-forest text-[9px] text-surface">✓</div>
                </div>
                <div class="mt-1 text-[12px] text-on-surface/55">{{ a.position }}</div>
              </div>
            </div>
          </Reveal>
        </div>
      </div>
    </section>

    <!-- ═══════════ TESTIMONIALS ═══════════ -->
    <section class="mx-auto max-w-[900px] px-margin-mobile py-section-gap text-center md:px-margin-desktop">
      <Reveal>
        <div class="mb-2 font-display text-[130px] leading-[0.6] text-secondary opacity-55">“</div>
        <div class="mb-10 text-[12px] font-medium uppercase tracking-[0.28em] text-on-surface/45">Word of mouth</div>
        <div class="relative min-h-[220px]">
          <div v-for="(t, i) in testimonials" :key="t.name"
               class="absolute inset-0 transition-opacity duration-1000 ease-smooth"
               :class="i === tIndex ? 'opacity-100' : 'pointer-events-none opacity-0'">
            <div class="font-display text-[clamp(1.4rem,2.4vw,2rem)] italic leading-[1.5] text-navy">“{{ t.quote }}”</div>
            <div class="mt-7 text-[13px] font-semibold uppercase tracking-[0.12em] text-on-surface">{{ t.name }}</div>
            <div class="mt-1 text-[12px] text-on-surface/50">{{ t.detail }}</div>
          </div>
        </div>
      </Reveal>
    </section>

    <!-- ═══════════ INSIGHTS ═══════════ -->
    <section v-if="data?.blogs?.length" class="mx-auto max-w-shell px-margin-mobile pb-section-gap md:px-margin-desktop">
      <Reveal class="mb-12">
        <div class="mb-4 text-[12px] font-medium uppercase tracking-[0.28em] text-secondary">04 — Notes from the field</div>
        <h2 class="font-display text-[clamp(2rem,4vw,4rem)] font-medium leading-[1.1] text-navy">Know before you buy</h2>
      </Reveal>
      <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <Reveal v-for="(b, i) in data.blogs" :key="b.id" :delay="i * 0.1">
          <NuxtLink :to="`/blog/${b.slug}`"
            class="group block overflow-hidden rounded-xl border border-on-surface/[0.07] bg-white transition-all duration-500 ease-smooth hover:-translate-y-1.5 hover:shadow-card">
            <div class="h-[200px] overflow-hidden">
              <img :src="b.cover_image" :alt="b.title" loading="lazy"
                   class="h-full w-full object-cover transition-transform duration-700 ease-smooth group-hover:scale-[1.06]" />
            </div>
            <div class="p-6">
              <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-secondary">Insight</div>
              <div class="mt-2.5 font-display text-[21px] leading-[1.3] text-navy">{{ b.title }}</div>
              <div class="mt-3 text-[13px] text-on-surface/55">{{ blogDate(b.published_at) }} · {{ b.author?.name || 'Aakash Realtor' }}</div>
            </div>
          </NuxtLink>
        </Reveal>
      </div>
    </section>

    <!-- ═══════════ CONTACT CTA ═══════════ -->
    <section id="contact" class="relative overflow-hidden bg-navy">
      <img src="https://images.unsplash.com/photo-1524661135-423995f22d0b?auto=format&fit=crop&w=2000&q=80"
           alt="" aria-hidden="true" loading="lazy" class="absolute inset-0 h-full w-full object-cover opacity-[0.22]" />
      <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(11,19,43,0.9) 0%, rgba(11,19,43,0.55) 100%)" />
      <div class="relative mx-auto grid max-w-shell grid-cols-1 items-center gap-16 px-margin-mobile py-[130px] md:grid-cols-2 md:px-margin-desktop">
        <Reveal>
          <div class="mb-5 text-[12px] font-medium uppercase tracking-[0.28em] text-secondary">Talk to us</div>
          <h2 class="font-display text-[clamp(2rem,4vw,4rem)] font-medium leading-[1.12] text-surface">Let's find the right ground for you</h2>
          <p class="mb-9 mt-6 max-w-[46ch] font-sans text-[17px] leading-[1.7] text-surface/65">
            Tell us what you're looking for — we respond within one working day.
            Usually faster, unless it's Dashain.
          </p>
          <div class="flex flex-wrap gap-3.5">
            <NuxtLink to="/contact"
              class="rounded-full bg-secondary px-[30px] py-4 font-sans text-[12px] font-semibold uppercase tracking-[0.12em] text-navy transition-all duration-300 ease-smooth hover:-translate-y-0.5 hover:bg-secondary-fixed-dim">
              Schedule Visit
            </NuxtLink>
            <a href="https://wa.me/9771442000" target="_blank" rel="noopener"
              class="rounded-full border border-surface/35 px-[30px] py-4 font-sans text-[12px] font-medium uppercase tracking-[0.12em] text-surface transition-all duration-300 ease-smooth hover:border-secondary hover:text-secondary">
              WhatsApp
            </a>
            <a href="tel:+97714420000"
              class="rounded-full border border-surface/35 px-[30px] py-4 font-sans text-[12px] font-medium uppercase tracking-[0.12em] text-surface transition-all duration-300 ease-smooth hover:border-secondary hover:text-secondary">
              Call +977 1 442 0000
            </a>
          </div>
        </Reveal>

        <Reveal :delay="0.15">
          <div class="rounded-2xl border border-surface/[0.18] bg-surface/10 p-9 backdrop-blur-xl">
            <div v-if="formState === 'sent'" class="py-10 text-center">
              <div class="font-display text-[28px] text-surface">Dhanyabad! 🙏</div>
              <p class="mt-3 font-sans text-[15px] text-surface/65">We've logged your requirement — an agent will call you within one working day.</p>
            </div>
            <form v-else class="grid gap-3.5" @submit.prevent="submitRequirement">
              <input v-model="form.name" required placeholder="Full name" class="cta-field" />
              <input v-model="form.phone" required placeholder="Phone" class="cta-field" />
              <div class="grid grid-cols-2 gap-3.5">
                <select v-model="form.transaction_type" class="cta-field cursor-pointer">
                  <option value="buy">Buying</option>
                  <option value="rent">Renting</option>
                </select>
                <select v-model="form.budget" class="cta-field cursor-pointer">
                  <option value="">Budget</option>
                  <option v-for="b in budgets" :key="b.value" :value="b.value">{{ b.label }}</option>
                </select>
              </div>
              <div class="grid grid-cols-2 gap-3.5">
                <select v-model="form.category_id" required class="cta-field cursor-pointer">
                  <option value="" disabled>Property type</option>
                  <option v-for="c in data?.categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <select v-model="form.city_id" required class="cta-field cursor-pointer">
                  <option value="" disabled>City</option>
                  <option v-for="c in data?.cities" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
              <textarea v-model="form.message" placeholder="Message" rows="3" class="cta-field resize-y" />
              <button type="submit" :disabled="formState === 'sending'"
                class="rounded-lg bg-secondary px-4 py-4 font-sans text-[13px] font-semibold uppercase tracking-[0.1em] text-navy transition-all duration-300 ease-smooth hover:-translate-y-0.5 hover:bg-secondary-fixed-dim hover:shadow-gold disabled:opacity-60">
                {{ formState === 'sending' ? 'Sending…' : 'Send inquiry' }}
              </button>
              <p v-if="formState === 'error'" class="text-[13px] text-red-300">{{ formError }}</p>
            </form>
          </div>
        </Reveal>
      </div>
    </section>
  </div>
</template>

<style scoped>
/* Frosted inputs on the navy contact panel. */
.cta-field {
  font-family: Inter, sans-serif;
  font-size: 14px;
  padding: 15px 18px;
  border-radius: 12px;
  border: 1px solid rgba(250, 248, 244, 0.2);
  background: rgba(11, 19, 43, 0.4);
  color: #faf8f4;
  outline: none;
  transition: border-color 0.3s, box-shadow 0.3s;
}
.cta-field::placeholder { color: rgba(250, 248, 244, 0.45); }
.cta-field:focus {
  border-color: #c7a76c;
  box-shadow: 0 0 0 3px rgba(199, 167, 108, 0.18);
}
select.cta-field option { color: #1c1c1c; }

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
