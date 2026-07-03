<script setup lang="ts">
import type { Property } from '~/types'

const props = defineProps<{ property: Property; dark?: boolean }>()

const img = computed(
  () => props.property.primary_image
    || props.property.images?.[0]?.sizes?.medium
    || props.property.images?.[0]?.url
    || null,
)

// "NPR 12.5 Cr" — editorial pricing per the design.
const price = computed(() => (props.property.price.formatted || '').replace(/^Rs\.?/i, 'NPR'))

const landArea = computed(() => {
  const a = props.property.area
  return a?.size ? `${a.size} ${a.unit}` : '—'
})
const roadAccess = computed(() => {
  const s = props.property.specs
  return s?.road_width ? `${s.road_width}ft Access` : (s?.facing ? `${s.facing} Facing` : '—')
})
const location = computed(() => {
  const l = props.property.location
  return [l?.area?.name, l?.city?.name].filter(Boolean).join(', ')
})
</script>

<template>
  <NuxtLink :to="property.url" class="group block cursor-pointer">
    <div class="mb-6 aspect-[4/3] overflow-hidden" :class="dark ? 'bg-primary-container' : 'bg-surface-container'">
      <img v-if="img" :src="img" :alt="property.title" loading="lazy"
           class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105" />
      <div v-else class="grid h-full w-full place-items-center text-on-surface-variant">
        <span class="material-symbols-outlined text-4xl">image</span>
      </div>
    </div>

    <h3 class="mb-1 font-display text-headline-sm transition-colors group-hover:text-secondary"
        :class="dark ? 'text-surface' : 'text-primary'">
      {{ property.title }}{{ location ? `, ${location.split(',')[0]}` : '' }}
    </h3>
    <p class="mb-4 font-sans text-body-md" :class="dark ? 'text-on-primary-container' : 'text-on-surface-variant'">
      {{ location || 'Nepal' }}
    </p>

    <!-- Technical spec table -->
    <div class="border-t pt-4" :class="dark ? 'border-on-surface-variant/30' : 'border-outline-variant'">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <span class="mb-1 block font-sans text-label-caps uppercase tracking-[0.1em]"
                :class="dark ? 'text-on-primary-container' : 'text-outline'">Land Area</span>
          <span class="font-sans text-technical-data" :class="dark ? 'text-surface' : 'text-primary'">{{ landArea }}</span>
        </div>
        <div>
          <span class="mb-1 block font-sans text-label-caps uppercase tracking-[0.1em]"
                :class="dark ? 'text-on-primary-container' : 'text-outline'">Road Access</span>
          <span class="font-sans text-technical-data" :class="dark ? 'text-surface' : 'text-primary'">{{ roadAccess }}</span>
        </div>
        <div class="col-span-2 mt-2">
          <span class="mb-1 block font-sans text-label-caps uppercase tracking-[0.1em]"
                :class="dark ? 'text-on-primary-container' : 'text-outline'">Pricing</span>
          <span class="font-sans text-lg font-medium" :class="dark ? 'text-secondary-container' : 'text-primary'">{{ price }}</span>
        </div>
      </div>
    </div>
  </NuxtLink>
</template>
