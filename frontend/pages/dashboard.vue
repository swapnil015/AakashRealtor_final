<script setup lang="ts">
import type { Property } from '~/types'
import { useAuthStore } from '~/stores/auth'

definePageMeta({ middleware: 'auth' })

const api = useApi()
const auth = useAuthStore()
const route = useRoute()

const statusFilter = ref('')
const { data, refresh } = await useAsyncData(
  'my-properties',
  () => api.get<Property[]>('/my/properties', statusFilter.value ? { status: statusFilter.value } : {}),
  { watch: [statusFilter] },
)

const justPosted = computed(() => route.query.posted === '1')

const statusColors: Record<string, string> = {
  active: 'bg-green-100 text-green-700',
  pending: 'bg-amber-100 text-amber-700',
  sold: 'bg-slate-200 text-slate-700',
  rented: 'bg-slate-200 text-slate-700',
  rejected: 'bg-red-100 text-red-700',
}

async function remove(p: Property) {
  if (!confirm(`Delete "${p.title}"?`)) return
  await api.del(`/properties/${p.id}`)
  refresh()
}

useSeoMeta({ title: 'My Dashboard', robots: 'noindex' })
</script>

<template>
  <div class="container-px py-28">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <p class="eyebrow">Welcome, {{ auth.user?.name?.split(' ')[0] }}</p>
        <h1 class="mt-2 font-display text-5xl font-semibold">My Listings</h1>
      </div>
      <NuxtLink to="/post" class="btn-gold">+ Post Property</NuxtLink>
    </div>

    <div v-if="justPosted" class="mt-6 rounded-xl bg-green-50 p-4 text-sm font-semibold text-green-700">
      ✓ Your listing was submitted and is pending review.
    </div>

    <div class="mt-8 flex gap-2">
      <button v-for="s in ['', 'active', 'pending', 'sold', 'rejected']" :key="s"
        class="rounded-full border px-4 py-2 text-sm font-semibold capitalize transition"
        :class="statusFilter === s ? 'border-gold bg-gold/10 text-gold-hover' : 'border-slate-200'"
        @click="statusFilter = s">{{ s || 'All' }}</button>
    </div>

    <div v-if="data?.data?.length" class="mt-8 space-y-3">
      <div v-for="p in data.data" :key="p.id" class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4">
        <img v-if="p.primary_image" :src="p.primary_image" class="h-20 w-28 rounded-xl object-cover" />
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2">
            <span class="rounded-full px-2.5 py-1 text-xs font-bold capitalize" :class="statusColors[p.status]">{{ p.status }}</span>
            <span class="text-xs text-muted">{{ p.views }} views</span>
          </div>
          <h3 class="mt-1 truncate font-display text-xl font-semibold">{{ p.title }}</h3>
          <p class="text-sm text-muted">{{ p.price.formatted }} · {{ p.location.city?.name }}</p>
        </div>
        <div class="flex gap-2">
          <NuxtLink :to="p.url" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold hover:border-gold">View</NuxtLink>
          <button class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50" @click="remove(p)">Delete</button>
        </div>
      </div>
    </div>

    <div v-else class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white p-16 text-center">
      <p class="font-display text-2xl">No listings yet.</p>
      <NuxtLink to="/post" class="btn-gold mt-6">Post your first property</NuxtLink>
    </div>
  </div>
</template>
