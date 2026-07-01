<script setup lang="ts">
import type { Category, City, Amenity } from '~/types'
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
const steps = ['Type', 'Details', 'Location', 'Photos', 'Review']

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
    // Create the listing (JSON), then upload images (multipart).
    const { data: created } = await api.post<{ id: number; slug: string }>('/properties', form)

    if (files.value.length) {
      const fd = new FormData()
      files.value.forEach((f) => fd.append('images[]', f))
      await api.post(`/properties/${created.id}/images`, fd)
    }
    navigateTo('/dashboard?posted=1')
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Could not submit. Please review and try again.'
    step.value = 2
  } finally {
    submitting.value = false
  }
}

useSeoMeta({ title: 'Post a Property', robots: 'noindex' })
</script>

<template>
  <div class="container-px max-w-3xl py-28">
    <h1 class="font-display text-5xl font-semibold">Post your property</h1>
    <p class="mt-2 text-muted">It takes about 3 minutes. We review every listing before it goes live.</p>

    <!-- Stepper -->
    <div class="mt-8 flex items-center gap-2">
      <template v-for="(s, i) in steps" :key="s">
        <div class="flex items-center gap-2">
          <div class="grid h-9 w-9 place-items-center rounded-full text-sm font-bold transition"
               :class="step > i + 1 ? 'bg-gold text-ink' : step === i + 1 ? 'bg-ink text-white' : 'bg-sand text-muted'">
            {{ step > i + 1 ? '✓' : i + 1 }}
          </div>
          <span class="hidden text-sm font-semibold sm:block" :class="step === i + 1 ? 'text-ink' : 'text-muted'">{{ s }}</span>
        </div>
        <div v-if="i < steps.length - 1" class="h-px flex-1 bg-slate-200" />
      </template>
    </div>

    <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-8">
      <!-- Step 1: Type -->
      <div v-show="step === 1" class="space-y-6">
        <div>
          <label class="eyebrow">I want to</label>
          <div class="mt-2 grid grid-cols-2 gap-3">
            <button v-for="t in ['buy','rent']" :key="t"
              class="rounded-xl border-2 p-4 text-left font-bold capitalize transition"
              :class="form.transaction_type === t ? 'border-gold bg-gold/5' : 'border-slate-200'"
              @click="form.transaction_type = t">
              {{ t === 'buy' ? 'Sell' : 'Rent out' }}
            </button>
          </div>
        </div>
        <div>
          <label class="eyebrow">Property category</label>
          <div class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-3">
            <button v-for="c in refs?.categories" :key="c.id"
              class="rounded-xl border-2 p-4 font-semibold transition"
              :class="form.category_id === c.id ? 'border-gold bg-gold/5' : 'border-slate-200'"
              @click="form.category_id = c.id">{{ c.name }}</button>
          </div>
        </div>
      </div>

      <!-- Step 2: Details -->
      <div v-show="step === 2" class="space-y-4">
        <input v-model="form.title" placeholder="Listing title (e.g. Hillside Glass Villa)" class="field" />
        <textarea v-model="form.description" rows="4" placeholder="Describe the property…" class="field resize-none" />
        <div class="grid grid-cols-2 gap-3">
          <input v-model.number="form.price" type="number" placeholder="Price (Rs.)" class="field" />
          <select v-model="form.price_unit" class="field">
            <option value="total">Total</option>
            <option value="per month">Per month</option>
            <option value="per year">Per year</option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <input v-model.number="form.area_size" type="number" placeholder="Area size" class="field" />
          <select v-model="form.area_unit" class="field">
            <option v-for="u in ['aana','ropani','paisa','daam','sqft','sqm']" :key="u" :value="u">{{ u }}</option>
          </select>
        </div>
        <div v-if="selectedCategory?.has_rooms" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <input v-model.number="form.bedrooms" type="number" placeholder="Beds" class="field" />
          <input v-model.number="form.bathrooms" type="number" placeholder="Baths" class="field" />
          <input v-model.number="form.floors" type="number" placeholder="Floors" class="field" />
          <input v-model.number="form.parking" type="number" placeholder="Parking" class="field" />
        </div>
        <div v-if="refs?.amenities?.length">
          <label class="eyebrow">Amenities</label>
          <div class="mt-2 flex flex-wrap gap-2">
            <button v-for="a in refs.amenities" :key="a.id"
              class="rounded-full border px-3 py-1.5 text-xs font-semibold transition"
              :class="form.amenities.includes(a.id) ? 'border-gold bg-gold/10 text-gold-hover' : 'border-slate-200'"
              @click="toggleAmenity(a.id)">{{ a.name }}</button>
          </div>
        </div>
      </div>

      <!-- Step 3: Location -->
      <div v-show="step === 3" class="space-y-4">
        <select v-model.number="form.city_id" class="field">
          <option :value="null">Select city</option>
          <option v-for="c in refs?.cities" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
        <select v-if="selectedCity?.areas?.length" v-model.number="form.area_id" class="field">
          <option :value="null">Select area (optional)</option>
          <option v-for="a in selectedCity.areas" :key="a.id" :value="a.id">{{ a.name }}</option>
        </select>
        <input v-model="form.address" placeholder="Street address / landmark" class="field" />
        <div class="grid grid-cols-2 gap-3">
          <input v-model.number="form.latitude" type="number" step="any" placeholder="Latitude (optional)" class="field" />
          <input v-model.number="form.longitude" type="number" step="any" placeholder="Longitude (optional)" class="field" />
        </div>
      </div>

      <!-- Step 4: Photos -->
      <div v-show="step === 4" class="space-y-4">
        <label class="block cursor-pointer rounded-2xl border-2 border-dashed border-slate-300 p-10 text-center transition hover:border-gold">
          <input type="file" accept="image/*" multiple class="hidden" @change="onFiles" />
          <div class="text-3xl">📷</div>
          <p class="mt-2 font-semibold">Click to upload photos</p>
          <p class="text-sm text-muted">Up to 20 images · JPG/PNG/WebP · max 8 MB each</p>
        </label>
        <div v-if="previews.length" class="grid grid-cols-3 gap-3 sm:grid-cols-4">
          <div v-for="(src, i) in previews" :key="i" class="relative overflow-hidden rounded-xl">
            <img :src="src" class="aspect-square w-full object-cover" />
            <button class="absolute right-1 top-1 grid h-6 w-6 place-items-center rounded-full bg-ink/80 text-xs text-white" @click="removeFile(i)">✕</button>
          </div>
        </div>
      </div>

      <!-- Step 5: Review -->
      <div v-show="step === 5" class="space-y-4">
        <h3 class="font-display text-2xl font-semibold">Review</h3>
        <dl class="grid grid-cols-2 gap-3 text-sm">
          <div><dt class="text-muted">Title</dt><dd class="font-semibold">{{ form.title || '—' }}</dd></div>
          <div><dt class="text-muted">Type</dt><dd class="font-semibold capitalize">{{ form.transaction_type }} · {{ selectedCategory?.name }}</dd></div>
          <div><dt class="text-muted">Price</dt><dd class="font-semibold">Rs. {{ form.price?.toLocaleString() }}</dd></div>
          <div><dt class="text-muted">City</dt><dd class="font-semibold">{{ selectedCity?.name }}</dd></div>
          <div><dt class="text-muted">Photos</dt><dd class="font-semibold">{{ files.length }}</dd></div>
        </dl>
        <p class="rounded-xl bg-sand p-4 text-sm text-muted">
          Your listing will be submitted as <b>pending</b> and reviewed by our team before going live.
        </p>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      </div>

      <!-- Nav -->
      <div class="mt-8 flex justify-between">
        <button v-if="step > 1" class="btn-ghost" @click="prev">← Back</button>
        <div class="ml-auto">
          <button v-if="step < 5" class="btn-gold" @click="next">Continue →</button>
          <button v-else :disabled="submitting" class="btn-gold" @click="submit">
            {{ submitting ? 'Submitting…' : 'Submit listing' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
