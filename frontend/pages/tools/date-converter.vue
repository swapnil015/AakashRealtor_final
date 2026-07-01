<script setup lang="ts">
const api = useApi()

const direction = ref<'ad2bs' | 'bs2ad'>('ad2bs')
const adDate = ref(new Date().toISOString().slice(0, 10))
const bs = reactive({ year: 2082, month: 1, day: 1 })
const result = ref<any>(null)
const error = ref('')

async function convert() {
  error.value = ''
  try {
    const payload = direction.value === 'ad2bs'
      ? { direction: 'ad2bs', date: adDate.value }
      : { direction: 'bs2ad', year: bs.year, month: bs.month, day: bs.day }
    const { data } = await api.post('/tools/date-converter', payload)
    result.value = data
  } catch (e: any) {
    error.value = e?.message || 'Conversion failed.'
    result.value = null
  }
}
await convert()

useSeoMeta({
  title: 'Nepali Date Converter (BS ⇄ AD)',
  description: 'Convert dates between Bikram Sambat (BS) and Gregorian (AD), 2000–2090 BS.',
})
</script>

<template>
  <div class="container-px max-w-2xl py-28">
    <p class="eyebrow">Tools</p>
    <h1 class="mt-3 font-display text-5xl font-semibold">Date Converter</h1>
    <p class="mt-2 text-muted">Bikram Sambat (BS) ⇄ Gregorian (AD).</p>

    <div class="mt-10 rounded-2xl border border-slate-200 bg-white p-7">
      <div class="mb-6 inline-flex rounded-lg bg-sand p-1">
        <button class="rounded-md px-5 py-2 text-sm font-bold transition"
          :class="direction === 'ad2bs' ? 'bg-gold text-ink' : 'text-muted'"
          @click="direction = 'ad2bs'; convert()">AD → BS</button>
        <button class="rounded-md px-5 py-2 text-sm font-bold transition"
          :class="direction === 'bs2ad' ? 'bg-gold text-ink' : 'text-muted'"
          @click="direction = 'bs2ad'; convert()">BS → AD</button>
      </div>

      <div v-if="direction === 'ad2bs'">
        <label class="eyebrow">Gregorian (AD) date</label>
        <input v-model="adDate" type="date" class="field mt-2" @change="convert" />
      </div>
      <div v-else class="grid grid-cols-3 gap-3">
        <div><label class="eyebrow">Year (BS)</label><input v-model.number="bs.year" type="number" min="2000" max="2090" class="field mt-2" @input="convert" /></div>
        <div><label class="eyebrow">Month</label><input v-model.number="bs.month" type="number" min="1" max="12" class="field mt-2" @input="convert" /></div>
        <div><label class="eyebrow">Day</label><input v-model.number="bs.day" type="number" min="1" max="32" class="field mt-2" @input="convert" /></div>
      </div>

      <p v-if="error" class="mt-4 text-sm text-red-600">{{ error }}</p>
    </div>

    <div v-if="result" class="mt-6 rounded-2xl bg-ink p-8 text-center text-white">
      <div v-if="direction === 'ad2bs'">
        <div class="eyebrow">Bikram Sambat</div>
        <div class="mt-2 font-display text-4xl font-bold text-gold">{{ result.formatted }}</div>
        <div class="mt-1 text-white/60">{{ result.day }} {{ result.month_name }} {{ result.year }}</div>
      </div>
      <div v-else>
        <div class="eyebrow">Gregorian</div>
        <div class="mt-2 font-display text-4xl font-bold text-gold">{{ result.ad }}</div>
        <div class="mt-1 text-white/60">{{ result.weekday }}</div>
      </div>
    </div>
  </div>
</template>
