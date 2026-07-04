<script setup lang="ts">
import type { Property } from '~/types'
import { ApiError } from '~/composables/useApi'

const route = useRoute()
const config = useRuntimeConfig()
const api = useApi()

const { data: result, error } = await useAsyncData(
  () => `property-${route.params.slug}`,
  () => api.get<Property>(`/properties/${route.params.slug}`),
)
if (error.value) throw createError({ statusCode: 404, statusMessage: 'Property not found', fatal: true })

const property = computed(() => result.value!.data)
const gallery = computed(() => property.value.images ?? [])
const activeImg = ref(0)
const heroImg = computed(
  () => gallery.value[activeImg.value]?.sizes?.large
    || gallery.value[activeImg.value]?.url
    || property.value.primary_image,
)
const sideImgs = computed(() => gallery.value.filter((_, i) => i !== activeImg.value).slice(0, 2))
const extraCount = computed(() => Math.max(0, gallery.value.length - 3))

// Editorial NPR price.
const price = computed(() => (property.value.price.formatted || '').replace(/^Rs\.?/i, 'NPR'))

// ── Land area → Ropani-Aana-Paisa-Daam breakdown ──
const TO_SQM: Record<string, number> = {
  ropani: 508.7376, aana: 31.7961, paisa: 7.949025, daam: 1.987256,
  sqft: 0.09290304, sqm: 1, kattha: 338.63154, bigha: 6772.6308, dhur: 16.931577,
}
const rapd = computed(() => {
  const size = Number(property.value.area?.size || 0)
  const unit = (property.value.area?.unit || 'aana').toLowerCase()
  if (!size || !TO_SQM[unit]) return null
  let sqm = size * TO_SQM[unit]
  const out: Record<string, number> = {}
  for (const u of ['ropani', 'aana', 'paisa', 'daam']) {
    out[u] = Math.floor(sqm / TO_SQM[u])
    sqm -= out[u] * TO_SQM[u]
  }
  return out
})

// Status chip + BS-style updated date.
const statusLabel = computed(() => {
  const s = property.value.status
  return s === 'active' ? 'Active' : s === 'sold' ? 'Sold' : s === 'rented' ? 'Rented' : s
})
const updatedAt = computed(() => property.value.published_at?.slice(0, 10) || '')

// Inquiry form.
const form = reactive({ name: '', phone: '', email: '', message: '', website: '', _ts: 0 })
const sending = ref(false)
const sent = ref(false)
const formError = ref('')
onMounted(() => { form._ts = Date.now() })

async function submitInquiry() {
  sending.value = true
  formError.value = ''
  try {
    await api.post('/inquiries', { property_id: property.value.id, ...form })
    sent.value = true
  } catch (e) {
    formError.value = e instanceof ApiError ? e.message : 'Could not send. Please try again.'
  } finally {
    sending.value = false
  }
}

const whatsappHref = computed(() => {
  const txt = `Hi, I'm interested in "${property.value.title}" (${config.public.siteUrl}${property.value.url})`
  return `https://wa.me/${property.value.agent?.phone || config.public.whatsapp}?text=${encodeURIComponent(txt)}`
})

const mapSrc = computed(() => {
  const { latitude, longitude } = property.value.location
  if (!latitude || !longitude) return null
  return `https://www.google.com/maps?q=${latitude},${longitude}&z=15&output=embed`
})

// ── SEO: meta + schema.org RealEstateListing JSON-LD ──
useSeoMeta({
  title: () => property.value.title,
  description: () => (property.value.description || '').slice(0, 155),
  ogTitle: () => property.value.title,
  ogImage: () => heroImg.value || undefined,
  ogType: 'website' as any,
})
useHead({
  script: [
    {
      type: 'application/ld+json',
      innerHTML: computed(() => JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'RealEstateListing',
        name: property.value.title,
        url: config.public.siteUrl + property.value.url,
        image: gallery.value.map((g) => g.sizes?.large || g.url).filter(Boolean),
        description: property.value.description,
        offers: {
          '@type': 'Offer',
          price: property.value.price.amount,
          priceCurrency: 'NPR',
          availability: 'https://schema.org/InStock',
        },
        address: {
          '@type': 'PostalAddress',
          addressLocality: property.value.location.city?.name,
          addressRegion: property.value.location.city?.district,
          addressCountry: 'NP',
          streetAddress: property.value.location.address,
        },
      })),
    },
  ],
})
</script>

<template>
  <div>
    <div class="mx-auto max-w-editorial px-margin-mobile pb-section-gap pt-8 md:px-0">
      <!-- Breadcrumbs, title & price -->
      <div class="mb-8 flex flex-col justify-between gap-6 md:flex-row md:items-end">
        <div>
          <nav class="mb-4 flex gap-2 font-sans text-label-caps uppercase tracking-[0.1em] text-on-surface-variant">
            <NuxtLink to="/" class="hover:text-secondary">Home</NuxtLink>
            <span>/</span>
            <NuxtLink :to="`/${property.transaction_type}House`" class="hover:text-secondary">Listings</NuxtLink>
            <span>/</span>
            <span class="text-primary">{{ property.location.city?.name || 'Nepal' }}</span>
          </nav>
          <h1 class="font-display text-headline-md text-primary">{{ property.title }}</h1>
          <p class="mt-2 flex items-center gap-2 font-sans text-body-md text-on-surface-variant">
            <span class="material-symbols-outlined text-[18px]">location_on</span>
            {{ property.location.address || [property.location.area?.name, property.location.city?.name].filter(Boolean).join(', ') }}, Nepal
          </p>
        </div>
        <div class="text-right">
          <p class="font-sans text-label-caps uppercase tracking-[0.1em] text-on-surface-variant">Price</p>
          <p class="font-display text-headline-md text-secondary">{{ price }}</p>
          <p v-if="property.price.negotiable" class="font-sans text-label-caps uppercase text-outline">Negotiable</p>
        </div>
      </div>

      <!-- Verified banner -->
      <div class="mb-8 flex flex-col justify-between gap-3 border border-outline-variant bg-surface-container-highest px-6 py-3 sm:flex-row sm:items-center">
        <div class="flex items-center gap-3">
          <span class="material-symbols-outlined filled text-secondary">verified</span>
          <span class="font-sans text-label-caps uppercase tracking-[0.1em] text-primary">Verified Listing • Aakash Realtor</span>
        </div>
        <div class="flex items-center gap-4">
          <span v-if="updatedAt" class="font-sans text-label-caps uppercase text-on-surface-variant">Last Updated: {{ updatedAt }}</span>
          <span class="bg-outline-variant px-3 py-1 text-[10px] font-bold uppercase tracking-wider">{{ statusLabel }}</span>
        </div>
      </div>

      <!-- Hero gallery (2fr/1fr grid) -->
      <section class="mb-content-gap grid gap-3" :class="sideImgs.length ? 'md:grid-cols-[2fr_1fr] md:grid-rows-[300px_300px]' : ''">
        <div class="group relative overflow-hidden rounded-xl bg-surface-container md:row-span-2">
          <img v-if="heroImg" :src="heroImg" :alt="property.title"
               class="h-full min-h-[300px] w-full object-cover transition-transform duration-700 group-hover:scale-105" />
          <div v-else class="grid h-full min-h-[300px] w-full place-items-center text-on-surface-variant">
            <span class="material-symbols-outlined text-5xl">image</span>
          </div>
        </div>
        <button v-for="(g, i) in sideImgs" :key="g.id"
          class="group relative hidden overflow-hidden rounded-xl bg-surface-container md:block"
          @click="activeImg = gallery.indexOf(g)">
          <img :src="g.sizes?.medium || g.url || ''" :alt="`${property.title} photo`"
               class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105" />
          <div v-if="i === 1 && extraCount > 0"
               class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition-opacity group-hover:opacity-100">
            <span class="font-sans text-label-caps uppercase tracking-[0.1em] text-white">+{{ extraCount }} More Photos</span>
          </div>
        </button>
      </section>

      <!-- Three-column layout -->
      <div class="grid grid-cols-1 gap-gutter md:grid-cols-12">
        <!-- Left: description + amenities -->
        <div class="space-y-content-gap md:col-span-5">
          <div v-if="property.description">
            <h3 class="mb-4 font-sans text-label-caps uppercase tracking-[0.1em] text-secondary">Property Description</h3>
            <div class="space-y-4 whitespace-pre-line font-sans text-body-md leading-relaxed text-on-surface-variant">
              {{ property.description }}
            </div>
          </div>
          <div v-if="property.amenities?.length">
            <h3 class="mb-4 font-sans text-label-caps uppercase tracking-[0.1em] text-secondary">Key Amenities</h3>
            <div class="grid grid-cols-2 gap-y-4">
              <div v-for="a in property.amenities" :key="a.id" class="flex items-center gap-3">
                <span class="material-symbols-outlined text-outline">check</span>
                <span class="font-sans text-body-md text-primary">{{ a.name }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Middle: technical specifications -->
        <div class="px-0 md:col-span-3 md:border-x md:border-outline-variant md:px-6">
          <h3 class="mb-6 font-sans text-label-caps uppercase tracking-[0.1em] text-secondary">Technical Specifications</h3>
          <div class="space-y-8">
            <!-- Land measurement (R-A-P-D) -->
            <div v-if="rapd">
              <p class="mb-2 font-sans text-[10px] font-semibold uppercase tracking-[0.1em] text-on-surface-variant">Land Area (R-A-P-D)</p>
              <div class="grid grid-cols-4 border border-outline-variant bg-surface-container-low text-center">
                <div v-for="(u, i) in (['ropani','aana','paisa','daam'] as const)" :key="u"
                     class="py-3" :class="i < 3 && 'border-r border-outline-variant'">
                  <span class="block font-display text-headline-sm text-primary">{{ rapd[u] }}</span>
                  <span class="font-sans text-[9px] font-semibold uppercase tracking-[0.1em] text-on-surface-variant">{{ u }}</span>
                </div>
              </div>
            </div>
            <!-- Road access -->
            <div v-if="property.specs.road_width">
              <p class="mb-2 font-sans text-[10px] font-semibold uppercase tracking-[0.1em] text-on-surface-variant">Road Access</p>
              <div class="border border-outline-variant p-4">
                <div class="mb-1 flex items-center justify-between">
                  <span class="font-sans text-technical-data font-bold text-primary">{{ property.specs.road_width }} FT</span>
                  <span class="bg-secondary/10 px-2 py-0.5 font-sans text-[9px] font-semibold uppercase tracking-[0.1em] text-secondary">Verified</span>
                </div>
                <p class="font-sans text-[13px] text-on-surface-variant">Access road as verified against lalpurja.</p>
              </div>
            </div>
            <!-- Key/value rows -->
            <div v-for="row in [
              property.specs.facing && { l: 'Facing', v: property.specs.facing },
              property.specs.bedrooms && { l: 'Bedrooms', v: property.specs.bedrooms },
              property.specs.bathrooms && { l: 'Bathrooms', v: property.specs.bathrooms },
              property.specs.floors && { l: 'Floors', v: `${property.specs.floors} Stories` },
              property.specs.parking != null && { l: 'Parking', v: property.specs.parking },
              property.area.size && { l: 'Total Area', v: `${property.area.size} ${property.area.unit}` },
            ].filter(Boolean)" :key="(row as any).l"
                 class="flex items-center justify-between border-b border-outline-variant py-4">
              <span class="font-sans text-label-caps uppercase tracking-[0.1em] text-on-surface-variant">{{ (row as any).l }}</span>
              <span class="font-sans text-technical-data font-bold text-primary">{{ (row as any).v }}</span>
            </div>
          </div>
        </div>

        <!-- Right: sticky inquiry + agent -->
        <div class="md:col-span-4">
          <div class="space-y-6 md:sticky md:top-[100px]">
            <!-- Inquire Privately (black card) -->
            <div class="rounded-2xl bg-primary p-8 text-white">
              <h4 class="mb-2 font-display text-headline-sm text-white">Inquire Privately</h4>
              <p class="mb-6 font-sans text-[14px] text-primary-fixed-dim">Schedule a private viewing or request documentation.</p>

              <div v-if="sent" class="border border-on-primary-container p-6 text-center">
                <span class="material-symbols-outlined text-3xl text-secondary-container">check_circle</span>
                <p class="mt-2 font-sans text-body-md font-bold">Inquiry sent.</p>
                <p class="mt-1 font-sans text-[13px] text-primary-fixed-dim">Our advisor will contact you shortly.</p>
              </div>

              <form v-else class="space-y-6" @submit.prevent="submitInquiry">
                <input v-model="form.name" placeholder="Full Name" required
                  class="w-full border-b border-on-primary-container bg-transparent py-2 font-sans text-body-md text-white transition-colors placeholder:text-on-primary-container focus:border-secondary-fixed-dim focus:outline-none" />
                <input v-model="form.phone" placeholder="Phone" required
                  class="w-full border-b border-on-primary-container bg-transparent py-2 font-sans text-body-md text-white transition-colors placeholder:text-on-primary-container focus:border-secondary-fixed-dim focus:outline-none" />
                <input v-model="form.email" type="email" placeholder="Email Address"
                  class="w-full border-b border-on-primary-container bg-transparent py-2 font-sans text-body-md text-white transition-colors placeholder:text-on-primary-container focus:border-secondary-fixed-dim focus:outline-none" />
                <textarea v-model="form.message" placeholder="Message" rows="3"
                  class="w-full border-b border-on-primary-container bg-transparent py-2 font-sans text-body-md text-white transition-colors placeholder:text-on-primary-container focus:border-secondary-fixed-dim focus:outline-none" />
                <!-- Honeypot (hidden from users) -->
                <input v-model="form.website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />
                <p v-if="formError" class="font-sans text-sm text-error-container">{{ formError }}</p>
                <button type="submit" :disabled="sending"
                  class="w-full rounded-lg bg-secondary py-4 font-sans text-label-caps uppercase tracking-[0.15em] text-navy transition-all duration-300 ease-smooth hover:-translate-y-0.5 hover:bg-secondary-fixed-dim">
                  {{ sending ? 'Sending…' : 'Request a Visit' }}
                </button>
                <a :href="whatsappHref" target="_blank" rel="noopener"
                   class="flex w-full items-center justify-center gap-2 rounded-lg border border-on-primary-container py-3 font-sans text-label-caps uppercase tracking-[0.15em] text-white transition-colors hover:border-secondary hover:text-secondary">
                  <span class="material-symbols-outlined text-[18px]">chat</span> WhatsApp
                </a>
              </form>
            </div>

            <!-- Assigned manager -->
            <div v-if="property.agent" class="rounded-xl bg-peach p-6">
              <h4 class="mb-4 font-sans text-[10px] font-semibold uppercase tracking-[0.1em] text-on-surface-variant">Listing Agent</h4>
              <div class="flex items-center gap-4">
                <div class="grid h-16 w-16 flex-shrink-0 place-items-center rounded-full bg-surface font-display text-2xl text-primary">
                  {{ property.agent.name?.charAt(0) }}
                </div>
                <div>
                  <p class="font-sans text-body-md font-bold text-primary">{{ property.agent.name }}</p>
                  <p class="font-sans text-[11px] font-semibold uppercase tracking-[0.1em] text-secondary">
                    {{ property.agent.role === 'admin' ? 'Senior Real Estate Advisor' : 'Real Estate Advisor' }}
                  </p>
                </div>
              </div>
              <div class="mt-6 flex gap-3">
                <a v-if="property.agent.phone" :href="`tel:${property.agent.phone}`"
                   class="flex flex-1 items-center justify-center gap-2 rounded-full border-[1.5px] border-secondary py-2 font-sans text-[11px] font-semibold uppercase tracking-[0.1em] text-primary transition-all hover:bg-secondary">
                  <span class="material-symbols-outlined text-[16px]">call</span> Call
                </a>
                <a :href="whatsappHref" target="_blank" rel="noopener"
                   class="flex flex-1 items-center justify-center gap-2 rounded-full border-[1.5px] border-secondary py-2 font-sans text-[11px] font-semibold uppercase tracking-[0.1em] text-primary transition-all hover:bg-secondary">
                  <span class="material-symbols-outlined text-[16px]">chat</span> WhatsApp
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Strategic Location (asymmetric map) -->
    <section v-if="mapSrc" class="bg-surface-container-low py-section-gap">
      <div class="mx-auto grid max-w-editorial grid-cols-1 items-center gap-gutter px-margin-mobile md:grid-cols-2 md:px-0">
        <div class="relative order-2 h-[450px] overflow-hidden border border-outline-variant bg-surface-dim md:order-1">
          <iframe :src="mapSrc" class="h-full w-full" loading="lazy" />
          <div class="absolute bottom-4 left-4 border border-outline-variant bg-white p-4 shadow-sm">
            <p class="font-sans text-[11px] font-semibold uppercase tracking-[0.1em] text-primary">Map Data Verified</p>
            <p class="font-sans text-[13px] text-on-surface-variant">
              {{ Number(property.location.latitude).toFixed(4) }}° N, {{ Number(property.location.longitude).toFixed(4) }}° E
            </p>
          </div>
        </div>
        <div class="order-1 space-y-6 md:order-2">
          <h3 class="font-display text-headline-md text-primary">Strategic Location</h3>
          <p class="font-sans text-body-md text-on-surface-variant">
            Situated in {{ property.location.area?.name || property.location.city?.name }},
            {{ property.location.city?.district || 'Nepal' }} — location and boundaries verified
            against the lalpurja before listing.
          </p>
          <ul class="space-y-4">
            <li class="flex items-start gap-4">
              <span class="font-sans font-bold text-secondary">01.</span>
              <div>
                <p class="font-sans text-body-md font-bold text-primary">Verified Address</p>
                <p class="font-sans text-[14px] text-on-surface-variant">{{ property.location.address || 'Full address shared after inquiry.' }}</p>
              </div>
            </li>
            <li class="flex items-start gap-4">
              <span class="font-sans font-bold text-secondary">02.</span>
              <div>
                <p class="font-sans text-body-md font-bold text-primary">City &amp; Zone</p>
                <p class="font-sans text-[14px] text-on-surface-variant">
                  {{ [property.location.area?.name, property.location.city?.name].filter(Boolean).join(', ') }}
                </p>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </section>

    <!-- Comparable assets -->
    <section v-if="property.similar?.length" class="mx-auto max-w-editorial px-margin-mobile py-section-gap md:px-0">
      <div class="mb-12 flex items-end justify-between border-b border-outline-variant pb-6">
        <div>
          <span class="mb-2 block font-sans text-label-caps uppercase tracking-widest text-secondary">Continue Browsing</span>
          <h2 class="font-display text-headline-md text-primary">Comparable Assets</h2>
        </div>
      </div>
      <div class="grid gap-gutter sm:grid-cols-2 lg:grid-cols-3">
        <PropertyCard v-for="p in property.similar.slice(0, 3)" :key="p.id" :property="p" />
      </div>
    </section>
  </div>
</template>
