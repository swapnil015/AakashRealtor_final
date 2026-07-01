<script setup lang="ts">
import type { Property } from '~/types'

const props = defineProps<{ property: Property; dark?: boolean }>()

const img = computed(
  () => props.property.primary_image
    || props.property.images?.[0]?.sizes?.medium
    || props.property.images?.[0]?.url
    || null,
)

const tag = computed(() => {
  const f = props.property.flags
  if (f.is_exclusive) return 'Exclusive'
  if (f.is_featured) return 'Featured'
  if (f.is_emerging) return 'Emerging'
  if (f.is_open_house) return 'Open House'
  if (f.is_by_owner) return 'By Owner'
  return props.property.transaction_type === 'rent' ? 'For Rent' : 'For Sale'
})

const specLine = computed(() => {
  const s = props.property.specs
  const parts: string[] = []
  if (s.bedrooms) parts.push(`${s.bedrooms} Bd`)
  if (s.bathrooms) parts.push(`${s.bathrooms} Ba`)
  if (props.property.area.size) parts.push(`${props.property.area.size} ${props.property.area.unit}`)
  return parts.join(' · ')
})
</script>

<template>
  <NuxtLink
    :to="property.url"
    class="group block overflow-hidden rounded-2xl border transition-all duration-300 ease-smooth hover:-translate-y-1.5"
    :class="dark
      ? 'border-ink-line bg-ink-soft hover:border-gold hover:shadow-[0_34px_60px_-34px_rgba(0,0,0,.7)]'
      : 'border-[#EFEDE7] bg-white hover:shadow-lift'"
  >
    <div class="relative h-56 overflow-hidden">
      <img v-if="img" :src="img" :alt="property.title" loading="lazy"
           class="h-full w-full object-cover transition-transform duration-[900ms] ease-smooth group-hover:scale-[1.09]" />
      <div v-else class="grid h-full w-full place-items-center bg-sand text-muted">No image</div>

      <!-- Gradient wash that deepens on hover for text legibility + depth. -->
      <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-ink/40 via-transparent to-transparent opacity-60 transition-opacity duration-500 group-hover:opacity-90" />

      <span class="absolute left-3 top-3 z-10 rounded-full bg-ink/80 px-3 py-1.5 text-[10.5px] font-bold uppercase tracking-wider text-white backdrop-blur transition-transform duration-300 ease-smooth group-hover:-translate-y-0.5">
        {{ tag }}
      </span>
      <span v-if="!dark" class="absolute bottom-3 left-3 z-10 rounded-lg bg-white/95 px-3 py-2 text-base font-bold text-ink">
        {{ property.price.formatted }}
      </span>
    </div>

    <div class="p-5">
      <h3 class="font-display text-2xl font-semibold leading-tight" :class="dark ? 'text-white' : 'text-ink'">
        {{ property.title }}
      </h3>
      <div class="mt-1 text-sm font-medium" :class="dark ? 'text-slate-400' : 'text-muted'">
        📍 {{ property.location.area?.name ? property.location.area.name + ', ' : '' }}{{ property.location.city?.name }}
      </div>

      <div class="mt-4 flex items-center justify-between border-t pt-3"
           :class="dark ? 'border-ink-line' : 'border-[#F1EEE8]'">
        <span class="text-[12.5px] font-semibold" :class="dark ? 'text-slate-400' : 'text-muted'">{{ specLine }}</span>
        <span v-if="dark" class="text-lg font-bold text-gold">{{ property.price.formatted }}</span>
        <span v-else class="translate-x-[-6px] text-[12.5px] font-bold text-gold-hover opacity-0 transition-all duration-300 ease-smooth group-hover:translate-x-0 group-hover:opacity-100">View →</span>
      </div>
    </div>
  </NuxtLink>
</template>
