<script setup lang="ts">
useSeoMeta({
  title: 'About Us',
  description: 'Eighteen years representing the Kathmandu valley\'s most considered homes — every listing inspected and lalpurja-verified.',
})
const stats = [['500+', 'Projects represented'], ['1,200+', 'Homes sold'], ['2,500+', 'Happy clients'], ['18 yrs', 'In the valley']]
</script>

<template>
  <div class="pt-28">
    <section class="container-px">
      <div class="max-w-2xl">
        <p class="eyebrow">Since 2008</p>
        <h1 class="mt-3 font-display text-6xl font-semibold leading-tight">Eighteen years of quiet expertise.</h1>
        <p class="mt-6 text-lg leading-relaxed text-muted">
          We represent the valley's most considered homes — and the people who build their lives in them.
          Every listing is inspected in person and verified against its lalpurja before it carries our name.
        </p>
      </div>
    </section>

    <section class="container-px py-20">
      <div class="grid gap-8 rounded-3xl bg-ink p-12 text-white sm:grid-cols-4">
        <div v-for="s in stats" :key="s[1]">
          <div class="font-display text-5xl font-bold text-gold">{{ s[0] }}</div>
          <div class="mt-2 text-sm text-white/60">{{ s[1] }}</div>
        </div>
      </div>
    </section>

    <section class="container-px pb-24">
      <div class="grid gap-10 lg:grid-cols-3">
        <div v-for="v in [
          ['Verified', 'Every property checked against its lalpurja before listing.'],
          ['Discreet', 'Off-market and appointment-only homes handled with care.'],
          ['End to end', 'From first viewing to registration — including NRN paperwork.'],
        ]" :key="v[0]" class="rounded-2xl border border-slate-200 bg-white p-8">
          <h3 class="font-display text-2xl font-semibold">{{ v[0] }}</h3>
          <p class="mt-3 text-muted">{{ v[1] }}</p>
        </div>
      </div>
    </section>
  </div>
</template>
