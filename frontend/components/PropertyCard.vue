<script setup lang="ts">
import type { Property } from '~/types'

const props = defineProps<{ property: Property; dark?: boolean }>()

const img = computed(
  () => props.property.primary_image
    || props.property.images?.[0]?.sizes?.medium
    || props.property.images?.[0]?.url
    || null,
)

// Nepali rupee glyph to match the editorial design (frontend-only presentation).
const price = computed(() => (props.property.price.formatted || '').replace(/^Rs\.?/i, 'रू'))

const areaText = computed(() => {
  const a = props.property.area
  return a?.size ? `${a.size} ${a.unit}` : '—'
})
const roadText = computed(() => {
  const s = props.property.specs
  if (s?.road_width) return `${s.road_width} ft`
  if (s?.facing) return s.facing
  return '—'
})
const statusText = computed(() => {
  const st = props.property.status
  if (st === 'active') return 'Available'
  if (st === 'sold') return 'Sold'
  if (st === 'rented') return 'Rented'
  return st
})
const location = computed(() => {
  const l = props.property.location
  return [l?.area?.name, l?.city?.name].filter(Boolean).join(', ') || 'Nepal'
})
</script>

<template>
  <NuxtLink :to="property.url" class="group block">
    <div class="aspect-[16/9] overflow-hidden">
      <img v-if="img" :src="img" :alt="property.title" loading="lazy"
           class="h-full w-full object-cover transition-transform duration-700 ease-smooth group-hover:scale-105" />
      <div v-else class="grid h-full w-full place-items-center"
           :class="dark ? 'bg-primary-container text-on-primary-container' : 'bg-surface-container text-on-surface-variant'">
        <span class="material-symbols-outlined text-4xl">image</span>
      </div>
    </div>

    <div class="mt-4 flex items-start justify-between gap-4">
      <div>
        <h4 class="font-display text-headline-sm italic leading-tight"
            :class="dark ? 'text-surface' : 'text-primary'">
          {{ property.title }}
        </h4>
        <p class="mt-1 font-sans text-body-md" :class="dark ? 'text-on-primary-container' : 'text-on-surface-variant'">
          {{ location }}
        </p>
      </div>
      <p class="whitespace-nowrap font-display text-headline-sm" :class="dark ? 'text-surface' : 'text-primary'">
        {{ price }}
      </p>
    </div>

    <!-- Technical data grid -->
    <div class="mt-4 grid grid-cols-3 border-y py-3"
         :class="dark ? 'border-on-surface-variant/30' : 'border-outline-variant'">
      <div class="border-r px-1 text-center" :class="dark ? 'border-on-surface-variant/30' : 'border-outline-variant'">
        <p class="font-sans text-[10px] font-semibold uppercase tracking-[0.1em]"
           :class="dark ? 'text-on-primary-container' : 'text-on-surface-variant'">Area</p>
        <p class="mt-0.5 font-sans text-technical-data" :class="dark ? 'text-surface' : 'text-primary'">{{ areaText }}</p>
      </div>
      <div class="border-r px-1 text-center" :class="dark ? 'border-on-surface-variant/30' : 'border-outline-variant'">
        <p class="font-sans text-[10px] font-semibold uppercase tracking-[0.1em]"
           :class="dark ? 'text-on-primary-container' : 'text-on-surface-variant'">Road</p>
        <p class="mt-0.5 font-sans text-technical-data" :class="dark ? 'text-surface' : 'text-primary'">{{ roadText }}</p>
      </div>
      <div class="px-1 text-center">
        <p class="font-sans text-[10px] font-semibold uppercase tracking-[0.1em]"
           :class="dark ? 'text-on-primary-container' : 'text-on-surface-variant'">Status</p>
        <p class="mt-0.5 font-sans text-technical-data"
           :class="dark ? 'text-secondary-container' : 'text-secondary'">{{ statusText }}</p>
      </div>
    </div>
  </NuxtLink>
</template>
