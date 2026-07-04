<script setup lang="ts">
import type { Category, City, Amenity, Area } from '~/types'
import { ApiError } from '~/composables/useApi'

definePageMeta({ middleware: 'auth' })

const api = useApi()

const { data: refs } = await useAsyncData('post-refs', async () => {
  const [cats, cities, ams] = await Promise.all([
    api.get<Category[]>('/categories'),
    api.get<City[]>('/cities'),
    api.get<Amenity[]>('/amenities'),
  ])
  return { categories: cats.data, cities: cities.data, amenities: ams.data }
})

const step = ref(1)
const steps = ['Identity', 'Location', 'Details', 'Media', 'Review']

// Editorial copy for the left rail, per step.
const stepCopy = [
  {
    q: 'What kind of property are we showcasing?',
    d: 'Please define the core nature of your transaction. This helps us tailor the subsequent forms for land measurements (Ropani/Aana) or architectural specifications.',
    tip: 'Accurate categorization ensures your property appears in the most relevant searches for high-net-worth investors.',
  },
  {
    q: 'Where is the asset located?',
    d: 'Pin the exact spot on the map of Nepal — we will detect the city, area and address for you, or enter it manually below.',
    tip: 'Precise coordinates let buyers verify road access and surroundings before requesting a viewing.',
  },
  {
    q: 'Define the technical profile.',
    d: 'Pricing, land measurement and structural specifications. Nepali units (aana, ropani, paisa, daam) are fully supported.',
    tip: 'Listings with complete technical data receive significantly more qualified inquiries.',
  },
  {
    q: 'Show it honestly.',
    d: 'Upload unedited photographs. Our editorial standard favors authentic documentation over heavy retouching.',
    tip: 'The first image becomes your cover. Landscape orientation (4:3) presents best in the catalog.',
  },
  {
    q: 'Confirm your listing.',
    d: 'Review the details below. Your listing is submitted for verification against its lalpurja before going live.',
    tip: 'Our team typically completes verification within one business day.',
  },
]

const form = reactive<Record<string, any>>({
  transaction_type: 'buy',
  category_id: null,
  title: '',
  description: '',
  price: null,
  price_unit: 'total',
  price_negotiable: false,
  area_size: null,
  area_unit: 'aana',
  bedrooms: null,
  bathrooms: null,
  floors: null,
  parking: null,
  facing: '',
  city_id: null,
  area_id: null,
  address: '',
  latitude: null,
  longitude: null,
  is_by_owner: true,
  amenities: [] as number[],
})

const files = ref<File[]>([])
const previews = ref<string[]>([])
const submitting = ref(false)
const error = ref('')

const selectedCity = computed(() => refs.value?.cities.find((c) => c.id === form.city_id))
const selectedCategory = computed(() => refs.value?.categories.find((c) => c.id === form.category_id))

// Material icons for the category tiles.
const catIcon = (slug: string) => ({
  house: 'home', land: 'landscape', flat: 'apartment', apartment: 'domain',
  commercial: 'storefront', residential: 'holiday_village',
}[slug] || 'home_work')

// ── Areas for the selected city (the /cities list omits areas) ──────────
const cityAreas = ref<Area[]>([])
const mapNote = ref('')

async function loadAreasFor(cityId: number | null): Promise<Area[]> {
  const city = refs.value?.cities.find((c) => c.id === cityId)
  if (!city) { cityAreas.value = []; return [] }
  try {
    const { data } = await api.get<Area[]>(`/cities/${city.public_id}/areas`)
    cityAreas.value = data ?? []
  } catch {
    cityAreas.value = []
  }
  return cityAreas.value
}

watch(() => form.city_id, async (id) => {
  const areas = await loadAreasFor(id)
  if (form.area_id && !areas.some((a) => a.id === form.area_id)) form.area_id = null
})

const norm = (s: string) => (s || '').toLowerCase().replace(/[^a-z]/g, '')

// ── Map pick → auto-fill the location fields ────────────────────────────
async function onMapPicked(p: {
  lat: number; lng: number; address: string
  city: string; area: string; district: string; province: string
}) {
  form.latitude = Number(p.lat.toFixed(7))
  form.longitude = Number(p.lng.toFixed(7))
  if (p.address) form.address = p.address

  const candidates = [p.city, p.district, p.area, p.province].map(norm).filter(Boolean)
  const city = refs.value?.cities.find((c) => {
    const n = norm(c.name)
    return candidates.some((cand) => cand.includes(n) || n.includes(cand))
  })

  if (city) {
    form.city_id = city.id
    const areas = await loadAreasFor(city.id)
    const areaName = norm(p.area)
    const match = areaName
      ? areas.find((a) => { const n = norm(a.name); return n.includes(areaName) || areaName.includes(n) })
      : null
    form.area_id = match?.id ?? null
    mapNote.value = match
      ? `Auto-filled: ${city.name} · ${match.name}`
      : `Auto-filled city: ${city.name}${p.area ? ` · area "${p.area}" not in our list — pick manually` : ''}`
  } else {
    mapNote.value = p.city
      ? `Detected "${p.city}" — not in our city list yet. Coordinates + address filled; choose the nearest city.`
      : 'Coordinates filled. Please choose the city.'
  }
}

function onFiles(e: Event) {
  const list = Array.from((e.target as HTMLInputElement).files ?? [])
  files.value = [...files.value, ...list].slice(0, 20)
  previews.value = files.value.map((f) => URL.createObjectURL(f))
}
function removeFile(i: number) {
  files.value.splice(i, 1)
  previews.value.splice(i, 1)
}
function toggleAmenity(id: number) {
  const i = form.amenities.indexOf(id)
  i >= 0 ? form.amenities.splice(i, 1) : form.amenities.push(id)
}

function next() { if (step.value < 5) step.value++ }
function prev() { if (step.value > 1) step.value-- }

async function submit() {
  submitting.value = true
  error.value = ''
  try {
    const { data: created } = await api.post<{ id: number; slug: string }>('/properties', form)

    if (files.value.length) {
      const fd = new FormData()
      files.value.forEach((f) => fd.append('images[]', f))
      await api.post(`/properties/${created.id}/images`, fd)
    }
    navigateTo('/dashboard?posted=1')
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Could not submit. Please review and try again.'
    step.value = 3
  } finally {
    submitting.value = false
  }
}

useSeoMeta({ title: 'List Your Legacy — Post a Property', robots: 'noindex' })
</script>

<template>
  <div class="mx-auto max-w-shell px-margin-mobile py-16 md:px-margin-desktop">
    <!-- Heading + step counter -->
    <div class="mb-10 flex items-end justify-between">
      <div>
        <p class="eyebrow mb-2">Create New Listing</p>
        <h1 class="font-display text-display-lg-mobile text-primary">List Your Legacy</h1>
      </div>
      <p class="font-sans text-label-caps uppercase tracking-[0.15em] text-on-surface-variant">Step {{ step }} of 5</p>
    </div>

    <!-- Stepper -->
    <div class="mb-16 flex items-center">
      <template v-for="(s, i) in steps" :key="s">
        <div class="flex flex-col items-center">
          <button
            class="grid h-10 w-10 place-items-center rounded-full border font-sans text-sm font-bold transition-colors"
            :class="step === i + 1
              ? 'border-primary bg-primary text-white'
              : step > i + 1
                ? 'border-primary bg-surface text-primary'
                : 'border-outline-variant bg-surface text-outline'"
            @click="i + 1 < step && (step = i + 1)"
          >
            <span v-if="step > i + 1" class="material-symbols-outlined text-[18px]">check</span>
            <span v-else>{{ i + 1 }}</span>
          </button>
          <span class="mt-2 font-sans text-[10px] font-semibold uppercase tracking-[0.15em]"
                :class="step === i + 1 ? 'text-primary' : 'text-outline'">{{ s }}</span>
        </div>
        <div v-if="i < steps.length - 1" class="mx-3 mb-5 h-px flex-1 bg-outline-variant" />
      </template>
    </div>

    <!-- Two-column: editorial rail + form -->
    <div class="grid gap-content-gap md:grid-cols-[340px_1fr]">
      <!-- Left rail -->
      <div class="space-y-8">
        <div>
          <h2 class="font-display text-headline-md leading-snug text-primary">{{ stepCopy[step - 1].q }}</h2>
          <p class="mt-4 font-sans text-body-md leading-relaxed text-on-surface-variant">{{ stepCopy[step - 1].d }}</p>
        </div>
        <div class="border border-outline-variant bg-surface-container-low p-6">
          <div class="mb-3 flex items-center gap-2">
            <span class="material-symbols-outlined text-secondary">info</span>
            <span class="font-sans text-label-caps uppercase tracking-[0.15em] text-primary">Pro Tip</span>
          </div>
          <p class="font-sans text-body-md italic text-on-surface-variant">{{ stepCopy[step - 1].tip }}</p>
        </div>
      </div>

      <!-- Right: form steps -->
      <div>
        <!-- Step 1: Identity -->
        <div v-show="step === 1" class="space-y-10">
          <div>
            <p class="mb-3 font-sans text-label-caps uppercase tracking-[0.15em] text-on-surface-variant">I want to</p>
            <div class="grid gap-gutter sm:grid-cols-2">
              <button v-for="t in (['buy','rent'] as const)" :key="t"
                class="relative flex flex-col items-center gap-3 border px-6 py-10 transition-colors"
                :class="form.transaction_type === t ? 'border-primary bg-surface-container-high' : 'border-outline-variant bg-surface hover:border-outline'"
                @click="form.transaction_type = t">
                <span v-if="form.transaction_type === t" class="material-symbols-outlined absolute right-4 top-4 text-primary">check_circle</span>
                <span class="material-symbols-outlined text-3xl text-on-surface-variant">{{ t === 'buy' ? 'sell' : 'key' }}</span>
                <span class="font-sans text-label-caps uppercase tracking-[0.15em] text-primary">{{ t === 'buy' ? 'Sell Property' : 'Rent Out' }}</span>
              </button>
            </div>
          </div>

          <div>
            <p class="mb-3 font-sans text-label-caps uppercase tracking-[0.15em] text-on-surface-variant">Property Category</p>
            <div class="grid grid-cols-2 gap-gutter lg:grid-cols-4">
              <button v-for="c in refs?.categories" :key="c.id"
                class="relative flex flex-col items-center gap-3 border px-4 py-8 transition-colors"
                :class="form.category_id === c.id ? 'border-primary bg-surface-container-high' : 'border-outline-variant bg-surface hover:border-outline'"
                @click="form.category_id = c.id">
                <span v-if="form.category_id === c.id" class="material-symbols-outlined absolute right-3 top-3 text-[18px] text-primary">check_circle</span>
                <span class="material-symbols-outlined text-2xl text-on-surface-variant">{{ catIcon(c.slug) }}</span>
                <span class="font-sans text-label-caps uppercase tracking-[0.1em] text-primary">{{ c.name }}</span>
              </button>
            </div>
          </div>

          <div class="border-t border-outline-variant pt-8">
            <p class="mb-3 font-sans text-label-caps uppercase tracking-[0.15em] text-on-surface-variant">Listing Title</p>
            <input v-model="form.title" placeholder="e.g. The Himalayan Heritage Estate" class="input-line text-headline-sm font-display" />
          </div>
        </div>

        <!-- Step 2: Location -->
        <div v-show="step === 2" class="space-y-6">
          <ClientOnly>
            <NepalLocationMap
              v-if="step === 2"
              :lat="form.latitude"
              :lng="form.longitude"
              @picked="onMapPicked"
            />
            <template #fallback>
              <div class="grid h-[380px] place-items-center border border-outline-variant bg-surface-container text-sm text-on-surface-variant">
                Loading map of Nepal…
              </div>
            </template>
          </ClientOnly>
          <p v-if="mapNote" class="font-sans text-label-caps uppercase tracking-[0.1em] text-secondary">{{ mapNote }}</p>

          <div class="border-t border-outline-variant pt-6">
            <p class="mb-4 font-sans text-label-caps uppercase tracking-[0.15em] text-on-surface-variant">Or enter manually</p>
            <div class="grid gap-6 sm:grid-cols-2">
              <div>
                <label class="mb-1 block font-sans text-label-caps uppercase text-outline">City</label>
                <select v-model.number="form.city_id" class="input-line">
                  <option :value="null">Select city</option>
                  <option v-for="c in refs?.cities" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
              <div v-if="cityAreas.length">
                <label class="mb-1 block font-sans text-label-caps uppercase text-outline">Area (optional)</label>
                <select v-model.number="form.area_id" class="input-line">
                  <option :value="null">Select area</option>
                  <option v-for="a in cityAreas" :key="a.id" :value="a.id">{{ a.name }}</option>
                </select>
              </div>
              <div class="sm:col-span-2">
                <label class="mb-1 block font-sans text-label-caps uppercase text-outline">Street Address / Landmark</label>
                <input v-model="form.address" placeholder="e.g. Budhanilkantha Height, Main Road" class="input-line" />
              </div>
              <div>
                <label class="mb-1 block font-sans text-label-caps uppercase text-outline">Latitude</label>
                <input v-model.number="form.latitude" type="number" step="any" class="input-line" />
              </div>
              <div>
                <label class="mb-1 block font-sans text-label-caps uppercase text-outline">Longitude</label>
                <input v-model.number="form.longitude" type="number" step="any" class="input-line" />
              </div>
            </div>
          </div>
        </div>

        <!-- Step 3: Details -->
        <div v-show="step === 3" class="space-y-8">
          <div>
            <label class="mb-1 block font-sans text-label-caps uppercase text-outline">Description</label>
            <textarea v-model="form.description" rows="4" placeholder="Describe the asset — history, construction, views…" class="input-line resize-none" />
          </div>

          <div class="grid gap-6 sm:grid-cols-3">
            <div>
              <label class="mb-1 block font-sans text-label-caps uppercase text-outline">Price (NPR)</label>
              <input v-model.number="form.price" type="number" placeholder="0" class="input-line" />
            </div>
            <div>
              <label class="mb-1 block font-sans text-label-caps uppercase text-outline">Price Basis</label>
              <select v-model="form.price_unit" class="input-line">
                <option value="total">Total</option>
                <option value="per month">Per month</option>
                <option value="per year">Per year</option>
              </select>
            </div>
            <label class="flex items-end gap-3 pb-3">
              <input v-model="form.price_negotiable" type="checkbox" class="h-4 w-4 rounded-none border-outline text-primary focus:ring-0" />
              <span class="font-sans text-label-caps uppercase text-on-surface-variant">Negotiable</span>
            </label>
          </div>

          <div class="grid gap-6 sm:grid-cols-2">
            <div>
              <label class="mb-1 block font-sans text-label-caps uppercase text-outline">Land / Built Area</label>
              <input v-model.number="form.area_size" type="number" step="any" placeholder="0" class="input-line" />
            </div>
            <div>
              <label class="mb-1 block font-sans text-label-caps uppercase text-outline">Unit</label>
              <select v-model="form.area_unit" class="input-line">
                <option v-for="u in ['aana','ropani','paisa','daam','sqft','sqm']" :key="u" :value="u">{{ u }}</option>
              </select>
            </div>
          </div>

          <div v-if="selectedCategory?.has_rooms" class="grid grid-cols-2 gap-6 sm:grid-cols-4">
            <div v-for="f in [
              { k: 'bedrooms', l: 'Beds' }, { k: 'bathrooms', l: 'Baths' },
              { k: 'floors', l: 'Floors' }, { k: 'parking', l: 'Parking' },
            ]" :key="f.k">
              <label class="mb-1 block font-sans text-label-caps uppercase text-outline">{{ f.l }}</label>
              <input v-model.number="form[f.k]" type="number" class="input-line" />
            </div>
          </div>

          <div v-if="refs?.amenities?.length">
            <p class="mb-3 font-sans text-label-caps uppercase tracking-[0.15em] text-on-surface-variant">Key Amenities</p>
            <div class="flex flex-wrap gap-2">
              <button v-for="a in refs.amenities" :key="a.id"
                class="border px-4 py-2 font-sans text-label-caps uppercase tracking-[0.1em] transition-colors"
                :class="form.amenities.includes(a.id)
                  ? 'border-primary bg-primary text-white'
                  : 'border-outline-variant text-primary hover:border-outline'"
                @click="toggleAmenity(a.id)">{{ a.name }}</button>
            </div>
          </div>
        </div>

        <!-- Step 4: Media -->
        <div v-show="step === 4" class="space-y-6">
          <label class="block cursor-pointer border border-dashed border-outline p-14 text-center transition-colors hover:border-primary hover:bg-surface-container-low">
            <input type="file" accept="image/*" multiple class="hidden" @change="onFiles" />
            <span class="material-symbols-outlined text-4xl text-on-surface-variant">add_a_photo</span>
            <p class="mt-3 font-sans text-label-caps uppercase tracking-[0.15em] text-primary">Upload Documentation</p>
            <p class="mt-1 font-sans text-body-md text-on-surface-variant">Up to 20 images · JPG/PNG/WebP · max 8 MB each</p>
          </label>
          <div v-if="previews.length" class="grid grid-cols-3 gap-4 sm:grid-cols-4">
            <div v-for="(src, i) in previews" :key="i" class="group relative overflow-hidden border border-outline-variant">
              <img :src="src" class="aspect-square w-full object-cover" />
              <span v-if="i === 0" class="absolute left-2 top-2 bg-primary px-2 py-0.5 font-sans text-[9px] font-semibold uppercase tracking-[0.1em] text-white">Cover</span>
              <button class="absolute right-2 top-2 grid h-6 w-6 place-items-center bg-primary/80 text-xs text-white" @click="removeFile(i)">✕</button>
            </div>
          </div>
        </div>

        <!-- Step 5: Review -->
        <div v-show="step === 5" class="space-y-8">
          <div class="border border-outline-variant">
            <div v-for="row in [
              { l: 'Title', v: form.title || '—' },
              { l: 'Intent', v: form.transaction_type === 'buy' ? 'Sell' : 'Rent Out' },
              { l: 'Category', v: selectedCategory?.name || '—' },
              { l: 'Location', v: [cityAreas.find(a => a.id === form.area_id)?.name, selectedCity?.name].filter(Boolean).join(', ') || '—' },
              { l: 'Price', v: form.price ? `NPR ${Number(form.price).toLocaleString('en-IN')}${form.price_unit !== 'total' ? ' / ' + form.price_unit.replace('per ', '') : ''}` : '—' },
              { l: 'Area', v: form.area_size ? `${form.area_size} ${form.area_unit}` : '—' },
              { l: 'Photos', v: `${files.length} image(s)` },
            ]" :key="row.l" class="flex items-center justify-between border-b border-outline-variant px-6 py-4 last:border-b-0">
              <span class="font-sans text-label-caps uppercase tracking-[0.15em] text-on-surface-variant">{{ row.l }}</span>
              <span class="text-right font-sans text-technical-data font-bold text-primary">{{ row.v }}</span>
            </div>
          </div>
          <div class="flex items-start gap-3 border border-outline-variant bg-surface-container-low p-6">
            <span class="material-symbols-outlined filled text-secondary">verified</span>
            <p class="font-sans text-body-md text-on-surface-variant">
              Your listing will be submitted as <b class="text-primary">pending</b> and verified
              against its lalpurja by our team before going live.
            </p>
          </div>
          <p v-if="error" class="font-sans text-sm text-error">{{ error }}</p>
        </div>

        <!-- Wizard nav -->
        <div class="mt-14 flex items-center justify-between border-t border-outline-variant pt-8">
          <button v-if="step > 1" class="font-sans text-label-caps uppercase tracking-[0.15em] text-on-surface-variant transition-colors hover:text-primary" @click="prev">
            ← Back
          </button>
          <span v-else class="font-sans text-label-caps uppercase tracking-[0.15em] text-outline">Save as Draft</span>
          <button v-if="step < 5"
            class="group flex items-center gap-3 bg-primary px-10 py-4 font-sans text-label-caps uppercase tracking-[0.15em] text-white transition-colors hover:bg-on-surface-variant"
            @click="next">
            Next Step
            <span class="material-symbols-outlined text-[18px] transition-transform group-hover:translate-x-1">arrow_forward</span>
          </button>
          <button v-else :disabled="submitting"
            class="group flex items-center gap-3 bg-primary px-10 py-4 font-sans text-label-caps uppercase tracking-[0.15em] text-white transition-colors hover:bg-on-surface-variant disabled:opacity-60"
            @click="submit">
            {{ submitting ? 'Submitting…' : 'Submit Listing' }}
            <span class="material-symbols-outlined text-[18px]">check</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
