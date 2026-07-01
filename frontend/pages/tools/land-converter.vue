<script setup lang="ts">
const api = useApi()

const units = ['ropani', 'aana', 'paisa', 'daam', 'bigha', 'kattha', 'dhur', 'sqft', 'sqm']
const value = ref(1)
const from = ref('ropani')
const to = ref('aana')
const result = ref<number | null>(null)
const breakdown = ref<any>(null)

async function convert() {
  const { data } = await api.post('/tools/land-converter', { value: value.value, from: from.value, to: to.value })
  result.value = data.result
  const bd = await api.post('/tools/land-converter', { value: value.value, from: from.value })
  breakdown.value = bd.data
}
await convert()

useSeoMeta({
  title: 'Land Unit Converter (Naptol)',
  description: 'Convert between Nepali land units — ropani, aana, paisa, daam, bigha, kattha — and metric.',
})
</script>

<template>
  <div class="container-px max-w-3xl py-28">
    <p class="eyebrow">Tools</p>
    <h1 class="mt-3 font-display text-5xl font-semibold">Land Converter</h1>
    <p class="mt-2 text-muted">Convert between Nepali land units and metric (Naptol).</p>

    <div class="mt-10 rounded-2xl border border-slate-200 bg-white p-7">
      <div class="grid gap-4 sm:grid-cols-[1fr_auto_1fr] sm:items-end">
        <div>
          <label class="eyebrow">Value</label>
          <input v-model.number="value" type="number" min="0" step="any" class="field mt-2" @input="convert" />
          <select v-model="from" class="field mt-2 capitalize" @change="convert">
            <option v-for="u in units" :key="u" :value="u" class="capitalize">{{ u }}</option>
          </select>
        </div>
        <div class="pb-3 text-center text-2xl text-gold">→</div>
        <div>
          <label class="eyebrow">Result</label>
          <div class="field mt-2 bg-sand font-bold">{{ result?.toLocaleString() ?? '—' }}</div>
          <select v-model="to" class="field mt-2 capitalize" @change="convert">
            <option v-for="u in units" :key="u" :value="u" class="capitalize">{{ u }}</option>
          </select>
        </div>
      </div>
    </div>

    <div v-if="breakdown" class="mt-6 rounded-2xl bg-ink p-7 text-white">
      <div class="eyebrow">Full breakdown of {{ value }} {{ from }}</div>
      <div class="mt-4 grid grid-cols-4 gap-4">
        <div v-for="(v, k) in breakdown.ropani_system" :key="k" class="text-center">
          <div class="font-display text-3xl font-bold text-gold">{{ v }}</div>
          <div class="text-xs capitalize text-white/60">{{ k }}</div>
        </div>
      </div>
      <div class="mt-5 flex justify-between border-t border-ink-line pt-4 text-sm">
        <span class="text-white/60">{{ breakdown.sqm }} m²</span>
        <span class="text-white/60">{{ breakdown.sqft }} sq ft</span>
      </div>
    </div>
  </div>
</template>
