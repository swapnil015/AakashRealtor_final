<script setup lang="ts">
import type { Branch } from '~/types'
const api = useApi()
const { data } = await useAsyncData('branches', () => api.get<Branch[]>('/branches'))
useSeoMeta({ title: 'Branches', description: 'Find an Aakash Realtor branch near you.' })
</script>

<template>
  <div class="container-px py-28">
    <p class="eyebrow">Find us</p>
    <h1 class="mt-3 font-display text-6xl font-semibold">Our Branches</h1>
    <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
      <div v-for="b in data?.data" :key="b.id" class="rounded-2xl border border-slate-200 bg-white p-6">
        <h3 class="font-display text-2xl font-semibold">{{ b.name }}</h3>
        <p class="mt-2 text-muted">{{ b.address }}</p>
        <p v-if="b.phone" class="mt-1 font-semibold">{{ b.phone }}</p>
      </div>
      <p v-if="!data?.data?.length" class="text-muted">Branch information coming soon.</p>
    </div>
  </div>
</template>
