<script setup lang="ts">
import type { Faq } from '~/types'
const api = useApi()
const { data } = await useAsyncData('faqs-page', () => api.get<Faq[]>('/faqs'))
const open = ref<number | null>(0)
useSeoMeta({ title: 'FAQs', description: 'Answers to common questions about buying, selling and renting with Aakash Realtor.' })
</script>

<template>
  <div class="container-px max-w-3xl py-28">
    <p class="eyebrow text-center">Good to know</p>
    <h1 class="mt-3 text-center font-display text-6xl font-semibold">Frequently Asked</h1>
    <div class="mt-12 divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white">
      <div v-for="(f, i) in data?.data" :key="f.id" class="px-6">
        <button class="flex w-full items-center justify-between py-5 text-left font-semibold" @click="open = open === i ? null : i">
          {{ f.question }}
          <span class="text-gold transition" :class="open === i ? 'rotate-45' : ''">+</span>
        </button>
        <p v-show="open === i" class="pb-5 text-muted">{{ f.answer }}</p>
      </div>
    </div>
  </div>
</template>
