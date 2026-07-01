<script setup lang="ts">
const api = useApi()
const { data } = await useAsyncData('team', () => api.get<any[]>('/team'))
useSeoMeta({ title: 'Our Team', description: 'Meet the Aakash Realtor team of valley property specialists.' })
</script>

<template>
  <div class="container-px py-28">
    <p class="eyebrow">The people</p>
    <h1 class="mt-3 font-display text-6xl font-semibold">Our Team</h1>
    <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
      <div v-for="m in data?.data" :key="m.id" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <img v-if="m.photo" :src="m.photo" :alt="m.name" class="aspect-square w-full object-cover" />
        <div v-else class="grid aspect-square place-items-center bg-sand font-display text-5xl text-gold">{{ m.name?.charAt(0) }}</div>
        <div class="p-5">
          <h3 class="font-display text-xl font-semibold">{{ m.name }}</h3>
          <p class="text-sm text-muted">{{ m.position }}</p>
        </div>
      </div>
      <p v-if="!data?.data?.length" class="text-muted">Team profiles coming soon.</p>
    </div>
  </div>
</template>
