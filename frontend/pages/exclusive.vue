<script setup lang="ts">
import type { Property } from '~/types'

const api = useApi()
const { data } = await useAsyncData('exclusive-page', () =>
  api.get<Property[]>('/properties/exclusive', { per_page: 12 }),
)

useSeoMeta({
  title: 'The Exclusive Collection',
  description: 'By-invitation, off-market and appointment-only properties curated by Aakash Realtor.',
})
</script>

<template>
  <div class="bg-ink pb-24 pt-32 text-white">
    <div class="container-px">
      <div class="text-center">
        <p class="eyebrow">By invitation</p>
        <h1 class="mt-3 font-display text-6xl font-semibold text-white">The Exclusive Collection</h1>
        <p class="mx-auto mt-4 max-w-xl text-white/70">
          Off-market estates and appointment-only residences, represented discreetly.
        </p>
      </div>
      <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <PropertyCard v-for="p in data?.data" :key="p.id" :property="p" dark />
      </div>
      <p v-if="!data?.data?.length" class="mt-10 text-center text-white/60">No exclusive listings at the moment.</p>
    </div>
  </div>
</template>
