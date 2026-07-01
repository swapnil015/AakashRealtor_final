<script setup lang="ts">
import type { Branch } from '~/types'

const api = useApi()
const { data } = await useAsyncData('contact-branches', () => api.get<Branch[]>('/branches'))

useSeoMeta({
  title: 'Contact',
  description: 'Speak with a valley property specialist at Aakash Realtor — usually within the hour.',
})
</script>

<template>
  <div class="container-px py-28">
    <p class="eyebrow">Speak with us</p>
    <h1 class="mt-3 font-display text-6xl font-semibold">Let's find your address.</h1>

    <div class="mt-12 grid gap-10 lg:grid-cols-[1fr_1fr]">
      <div class="space-y-4">
        <div v-for="b in data?.data" :key="b.id" class="rounded-2xl border border-slate-200 bg-white p-6">
          <div class="flex items-center gap-2">
            <h3 class="font-display text-2xl font-semibold">{{ b.name }}</h3>
            <span v-if="b.is_head_office" class="rounded-full bg-gold/10 px-2.5 py-1 text-xs font-bold text-gold-hover">HQ</span>
          </div>
          <p class="mt-2 text-muted">{{ b.address }}</p>
          <p v-if="b.phone" class="mt-1 font-semibold">{{ b.phone }}</p>
          <a v-if="b.map_url" :href="b.map_url" target="_blank" class="mt-2 inline-block text-sm font-semibold text-gold-hover">View on map →</a>
        </div>
        <p v-if="!data?.data?.length" class="text-muted">
          Aakash Realtor HQ · Durbar Marg, Kathmandu 44600 · +977 1 4002200
        </p>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-7">
        <h2 class="font-display text-3xl font-semibold">Send a message</h2>
        <form class="mt-5 space-y-3" @submit.prevent>
          <input placeholder="Your name" class="field" />
          <input placeholder="Email" class="field" />
          <input placeholder="Phone" class="field" />
          <textarea placeholder="How can we help?" rows="4" class="field resize-none" />
          <button class="btn-gold w-full">Send message</button>
        </form>
      </div>
    </div>
  </div>
</template>
