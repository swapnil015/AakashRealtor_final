<script setup lang="ts">
import type { Amenity, Category, City } from '~/types'

// v-model object holding all active filters.
const model = defineModel<{
  transaction_type?: string
  category?: string
  city?: string
  min_price?: number | null
  max_price?: number | null
  bedrooms?: number | null
  bathrooms?: number | null
  min_area?: number | null
  max_area?: number | null
  amenities?: string[]
}>({ required: true })

const props = defineProps<{
  categories: Category[]
  cities: City[]
  amenities: Amenity[]
}>()

const emit = defineEmits<{ apply: []; reset: [] }>()

function toggleAmenity(slug: string) {
  const set = new Set(model.value.amenities ?? [])
  set.has(slug) ? set.delete(slug) : set.add(slug)
  model.value.amenities = [...set]
}
</script>

<template>
  <aside class="space-y-7 rounded-2xl border border-slate-200 bg-white p-6">
    <div class="flex items-center justify-between">
      <h3 class="font-display text-2xl font-semibold">Filters</h3>
      <button class="text-xs font-semibold text-gold-hover hover:underline" @click="emit('reset')">Reset</button>
    </div>

    <!-- Transaction -->
    <div>
      <label class="eyebrow">Looking to</label>
      <div class="mt-2 grid grid-cols-2 gap-2">
        <button v-for="t in ['buy','rent']" :key="t"
          class="rounded-lg border px-3 py-2.5 text-sm font-semibold capitalize transition"
          :class="model.transaction_type === t ? 'border-gold bg-gold/10 text-gold-hover' : 'border-slate-200 hover:border-gold'"
          @click="model.transaction_type = model.transaction_type === t ? undefined : t">{{ t }}</button>
      </div>
    </div>

    <!-- Category -->
    <div>
      <label class="eyebrow">Category</label>
      <select v-model="model.category" class="field mt-2">
        <option :value="undefined">All categories</option>
        <option v-for="c in categories" :key="c.id" :value="c.slug">{{ c.name }}</option>
      </select>
    </div>

    <!-- City -->
    <div>
      <label class="eyebrow">City</label>
      <select v-model="model.city" class="field mt-2">
        <option :value="undefined">All cities</option>
        <option v-for="c in cities" :key="c.id" :value="String(c.public_id)">{{ c.name }}</option>
      </select>
    </div>

    <!-- Price -->
    <div>
      <label class="eyebrow">Price range (Rs.)</label>
      <div class="mt-2 grid grid-cols-2 gap-2">
        <input v-model.number="model.min_price" type="number" min="0" placeholder="Min" class="field" />
        <input v-model.number="model.max_price" type="number" min="0" placeholder="Max" class="field" />
      </div>
    </div>

    <!-- Beds / baths -->
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="eyebrow">Beds</label>
        <select v-model.number="model.bedrooms" class="field mt-2">
          <option :value="null">Any</option>
          <option v-for="n in 6" :key="n" :value="n">{{ n }}+</option>
        </select>
      </div>
      <div>
        <label class="eyebrow">Baths</label>
        <select v-model.number="model.bathrooms" class="field mt-2">
          <option :value="null">Any</option>
          <option v-for="n in 6" :key="n" :value="n">{{ n }}+</option>
        </select>
      </div>
    </div>

    <!-- Amenities -->
    <div v-if="amenities.length">
      <label class="eyebrow">Amenities</label>
      <div class="mt-2 flex flex-wrap gap-2">
        <button v-for="a in amenities" :key="a.id"
          class="rounded-full border px-3 py-1.5 text-xs font-semibold transition"
          :class="(model.amenities ?? []).includes(a.slug) ? 'border-gold bg-gold/10 text-gold-hover' : 'border-slate-200 hover:border-gold'"
          @click="toggleAmenity(a.slug)">{{ a.name }}</button>
      </div>
    </div>

    <button class="btn-gold w-full" @click="emit('apply')">Apply filters</button>
  </aside>
</template>
