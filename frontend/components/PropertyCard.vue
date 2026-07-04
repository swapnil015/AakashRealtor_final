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
const price = computed(() => (props.property.price.formatted || '').replace(/^Rs\.?\s?/i, 'NPR '))

const location = computed(() => {
  const l = props.property.location
  return [l?.area?.name, l?.city?.name].filter(Boolean).join(', ') || 'Nepal'
})

const specs = computed(() => {
  const s = props.property.specs
  if (s?.bedrooms) return `${s.bedrooms} bed · ${s.bathrooms ?? '—'} bath`
  const a = props.property.area
  return a?.size ? `${a.size} ${a.unit}` : ''
})

const verified = computed(() => props.property.flags?.is_featured || props.property.flags?.is_exclusive)
</script>

<template>
  <NuxtLink :to="property.url"
    class="group block overflow-hidden rounded-xl border border-on-surface/[0.07] bg-white transition-all duration-500 ease-smooth hover:-translate-y-1.5 hover:shadow-card">
    <div class="relative h-[230px] overflow-hidden" :class="!img && 'bg-surface-container'">
      <img v-if="img" :src="img" :alt="property.title" loading="lazy"
           class="h-full w-full object-cover transition-transform duration-700 ease-smooth group-hover:scale-[1.06]" />
      <div v-else class="grid h-full w-full place-items-center text-on-surface-variant">
        <span class="material-symbols-outlined text-4xl">image</span>
      </div>
      <div v-if="verified"
           class="absolute left-3.5 top-3.5 rounded-full bg-forest/90 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-surface">
        ✓ Verified
      </div>
      <div v-if="property.transaction_type === 'rent'"
           class="absolute right-3.5 top-3.5 rounded-full bg-navy/80 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-surface backdrop-blur-sm">
        For Rent
      </div>
    </div>

    <div class="px-6 pb-[26px] pt-[22px]">
      <div class="mb-2 truncate text-[11px] uppercase tracking-[0.18em] text-on-surface/50">
        {{ location }}<template v-if="property.category"> · {{ property.category.name }}</template>
      </div>
      <div class="line-clamp-2 font-display text-[21px] leading-[1.25] text-navy">{{ property.title }}</div>
      <div class="mt-3.5 flex items-center justify-between gap-3">
        <div class="whitespace-nowrap text-[16px] font-semibold text-secondary transition-all group-hover:[text-shadow:0_0_18px_rgba(199,167,108,0.6)]">
          {{ price }}
        </div>
        <div class="truncate text-[12px] text-on-surface/50">{{ specs }}</div>
      </div>
    </div>
  </NuxtLink>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
