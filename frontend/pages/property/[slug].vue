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

const specs = computed(() => {
  const s = property.value.specs
  return [
    s.bedrooms && { label: 'Bedrooms', value: s.bedrooms },
    s.bathrooms && { label: 'Bathrooms', value: s.bathrooms },
    s.floors && { label: 'Floors', value: s.floors },
    s.parking != null && { label: 'Parking', value: s.parking },
    property.value.area.size && { label: 'Area', value: `${property.value.area.size} ${property.value.area.unit}` },
    s.road_width && { label: 'Road', value: `${s.road_width} ft` },
    s.facing && { label: 'Facing', value: s.facing },
  ].filter(Boolean) as { label: string; value: string | number }[]
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
  <div class="pt-24">
    <!-- Gallery -->
    <section class="container-px">
      <nav class="mb-4 text-sm text-muted">
        <NuxtLink to="/" class="hover:text-gold">Home</NuxtLink> /
        <NuxtLink :to="`/${property.transaction_type}House`" class="hover:text-gold capitalize">{{ property.transaction_type }}</NuxtLink> /
        <span class="text-ink">{{ property.title }}</span>
      </nav>

      <div class="grid gap-3 lg:grid-cols-[2fr_1fr]">
        <div class="relative overflow-hidden rounded-2xl bg-sand">
          <img v-if="heroImg" :src="heroImg" :alt="property.title" class="aspect-[16/10] w-full object-cover" />
          <div v-else class="grid aspect-[16/10] place-items-center text-muted">No image</div>
          <span class="absolute left-4 top-4 rounded-full bg-ink/80 px-4 py-2 text-xs font-bold uppercase tracking-wider text-white backdrop-blur">
            {{ property.transaction_type === 'rent' ? 'For Rent' : 'For Sale' }}
          </span>
        </div>
        <div class="grid grid-cols-4 gap-3 lg:grid-cols-2">
          <button v-for="(g, i) in gallery.slice(0, 4)" :key="g.id"
            class="overflow-hidden rounded-xl border-2 transition"
            :class="activeImg === i ? 'border-gold' : 'border-transparent'"
            @click="activeImg = i">
            <img :src="g.sizes?.small || g.url || ''" :alt="`${property.title} ${i + 1}`" class="aspect-square w-full object-cover" />
          </button>
        </div>
      </div>
    </section>

    <!-- Body -->
    <section class="container-px grid gap-10 py-12 lg:grid-cols-[1fr_380px]">
      <div>
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h1 class="font-display text-4xl font-semibold sm:text-5xl">{{ property.title }}</h1>
            <p class="mt-2 text-muted">📍 {{ property.location.address || property.location.city?.name }}</p>
          </div>
          <div class="text-right">
            <div class="font-display text-4xl font-bold text-gold-hover">{{ property.price.formatted }}</div>
            <div v-if="property.price.negotiable" class="text-xs font-semibold text-muted">Negotiable</div>
          </div>
        </div>

        <!-- Specs -->
        <div class="mt-8 grid grid-cols-2 gap-4 rounded-2xl border border-slate-200 bg-white p-6 sm:grid-cols-4">
          <div v-for="s in specs" :key="s.label">
            <div class="text-xs font-semibold uppercase tracking-wide text-muted">{{ s.label }}</div>
            <div class="mt-1 font-display text-2xl font-semibold">{{ s.value }}</div>
          </div>
        </div>

        <!-- Description -->
        <div v-if="property.description" class="mt-10">
          <h2 class="font-display text-3xl font-semibold">About this property</h2>
          <p class="mt-4 whitespace-pre-line leading-relaxed text-muted">{{ property.description }}</p>
        </div>

        <!-- Amenities -->
        <div v-if="property.amenities?.length" class="mt-10">
          <h2 class="font-display text-3xl font-semibold">Amenities</h2>
          <div class="mt-4 flex flex-wrap gap-2.5">
            <span v-for="a in property.amenities" :key="a.id"
              class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold">✓ {{ a.name }}</span>
          </div>
        </div>

        <!-- Map -->
        <div v-if="mapSrc" class="mt-10">
          <h2 class="font-display text-3xl font-semibold">Location</h2>
          <iframe :src="mapSrc" class="mt-4 h-80 w-full rounded-2xl border border-slate-200" loading="lazy" />
        </div>
      </div>

      <!-- Contact sidebar -->
      <aside class="h-fit lg:sticky lg:top-24">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
          <div v-if="property.agent" class="mb-5 flex items-center gap-3 border-b border-slate-100 pb-5">
            <div class="grid h-12 w-12 place-items-center rounded-full bg-gold/15 font-display text-xl font-bold text-gold-hover">
              {{ property.agent.name?.charAt(0) }}
            </div>
            <div>
              <div class="font-bold">{{ property.agent.name }}</div>
              <div class="text-xs capitalize text-muted">{{ property.agent.role }}</div>
            </div>
          </div>

          <div v-if="sent" class="rounded-xl bg-green-50 p-6 text-center">
            <div class="text-3xl">✓</div>
            <p class="mt-2 font-semibold">Inquiry sent!</p>
            <p class="mt-1 text-sm text-muted">An agent will contact you shortly.</p>
          </div>

          <form v-else class="space-y-3" @submit.prevent="submitInquiry">
            <h3 class="font-display text-2xl font-semibold">Request a viewing</h3>
            <input v-model="form.name" placeholder="Your name" required class="field" />
            <input v-model="form.phone" placeholder="Phone" required class="field" />
            <input v-model="form.email" type="email" placeholder="Email (optional)" class="field" />
            <textarea v-model="form.message" placeholder="I'd like to know more about…" rows="3" class="field resize-none" />
            <!-- Honeypot (hidden from users) -->
            <input v-model="form.website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />
            <p v-if="formError" class="text-sm text-red-600">{{ formError }}</p>
            <button type="submit" :disabled="sending" class="btn-gold w-full">
              {{ sending ? 'Sending…' : 'Send Inquiry' }}
            </button>
            <a :href="whatsappHref" target="_blank" rel="noopener"
               class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#25D366] py-3 font-bold text-white transition hover:-translate-y-0.5">
              💬 WhatsApp
            </a>
          </form>
        </div>
      </aside>
    </section>

    <!-- Similar -->
    <section v-if="property.similar?.length" class="container-px py-16">
      <h2 class="mb-8 font-display text-4xl font-semibold">Similar properties</h2>
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <PropertyCard v-for="p in property.similar" :key="p.id" :property="p" />
      </div>
    </section>
  </div>
</template>
