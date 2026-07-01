<script setup lang="ts">
const api = useApi()
const { data } = await useAsyncData('blogs', () => api.get<any[]>('/blogs', { per_page: 12 }))
useSeoMeta({ title: 'Blog', description: 'Property insights, market trends and buying guides from Aakash Realtor.' })
</script>

<template>
  <div class="container-px py-28">
    <p class="eyebrow">Insights</p>
    <h1 class="mt-3 font-display text-6xl font-semibold">The Journal</h1>
    <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
      <NuxtLink v-for="b in data?.data" :key="b.id" :to="`/blog/${b.slug}`"
        class="group overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:-translate-y-1.5 hover:shadow-lift">
        <img v-if="b.cover_image" :src="b.cover_image" :alt="b.title" class="aspect-[16/10] w-full object-cover transition group-hover:scale-105" />
        <div class="p-6">
          <h3 class="font-display text-2xl font-semibold leading-tight">{{ b.title }}</h3>
          <p class="mt-2 line-clamp-2 text-sm text-muted">{{ b.excerpt }}</p>
        </div>
      </NuxtLink>
      <p v-if="!data?.data?.length" class="text-muted">No articles published yet.</p>
    </div>
  </div>
</template>
