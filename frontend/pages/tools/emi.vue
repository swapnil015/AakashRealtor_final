<script setup lang="ts">
const api = useApi()

const principal = ref(5000000)
const rate = ref(12)
const tenure = ref(120) // months
const result = ref<any>(null)
const loading = ref(false)

async function calculate() {
  loading.value = true
  try {
    const { data } = await api.post('/tools/emi', {
      principal: principal.value,
      annual_rate: rate.value,
      tenure_months: tenure.value,
      schedule: true,
    })
    result.value = data
  } finally {
    loading.value = false
  }
}
await calculate()

const npr = (n: number) => 'Rs. ' + Number(n).toLocaleString('en-IN', { maximumFractionDigits: 0 })

useSeoMeta({
  title: 'EMI Calculator',
  description: 'Calculate your monthly home-loan EMI, total interest and full amortization schedule.',
})
</script>

<template>
  <div class="container-px max-w-4xl py-28">
    <p class="eyebrow">Tools</p>
    <h1 class="mt-3 font-display text-5xl font-semibold">EMI Calculator</h1>
    <p class="mt-2 text-muted">Estimate your monthly home-loan payment.</p>

    <div class="mt-10 grid gap-8 lg:grid-cols-[1fr_1fr]">
      <!-- Inputs -->
      <div class="space-y-6 rounded-2xl border border-slate-200 bg-white p-7">
        <div>
          <div class="flex justify-between"><label class="eyebrow">Loan amount</label><span class="text-sm font-bold">{{ npr(principal) }}</span></div>
          <input v-model.number="principal" type="range" min="500000" max="100000000" step="500000" class="mt-3 w-full accent-gold" @input="calculate" />
        </div>
        <div>
          <div class="flex justify-between"><label class="eyebrow">Interest rate</label><span class="text-sm font-bold">{{ rate }}%</span></div>
          <input v-model.number="rate" type="range" min="5" max="20" step="0.25" class="mt-3 w-full accent-gold" @input="calculate" />
        </div>
        <div>
          <div class="flex justify-between"><label class="eyebrow">Tenure</label><span class="text-sm font-bold">{{ (tenure / 12).toFixed(1) }} yrs</span></div>
          <input v-model.number="tenure" type="range" min="12" max="360" step="12" class="mt-3 w-full accent-gold" @input="calculate" />
        </div>
      </div>

      <!-- Result -->
      <div class="rounded-2xl bg-ink p-7 text-white">
        <div class="text-sm text-white/60">Monthly EMI</div>
        <div class="mt-1 font-display text-5xl font-bold text-gold">{{ result ? npr(result.emi) : '—' }}</div>
        <dl class="mt-7 space-y-3 border-t border-ink-line pt-5 text-sm">
          <div class="flex justify-between"><dt class="text-white/60">Principal</dt><dd class="font-semibold">{{ npr(principal) }}</dd></div>
          <div class="flex justify-between"><dt class="text-white/60">Total interest</dt><dd class="font-semibold">{{ result ? npr(result.total_interest) : '—' }}</dd></div>
          <div class="flex justify-between"><dt class="text-white/60">Total payable</dt><dd class="font-semibold">{{ result ? npr(result.total_payable) : '—' }}</dd></div>
        </dl>
      </div>
    </div>

    <!-- Schedule -->
    <details v-if="result?.schedule" class="mt-8 rounded-2xl border border-slate-200 bg-white p-6">
      <summary class="cursor-pointer font-semibold">View amortization schedule ({{ result.schedule.length }} months)</summary>
      <div class="mt-4 max-h-96 overflow-auto">
        <table class="w-full text-left text-sm">
          <thead class="sticky top-0 bg-white text-muted">
            <tr><th class="py-2">Month</th><th>EMI</th><th>Principal</th><th>Interest</th><th>Balance</th></tr>
          </thead>
          <tbody>
            <tr v-for="row in result.schedule" :key="row.month" class="border-t border-slate-100">
              <td class="py-2">{{ row.month }}</td>
              <td>{{ npr(row.emi) }}</td>
              <td>{{ npr(row.principal) }}</td>
              <td>{{ npr(row.interest) }}</td>
              <td>{{ npr(row.balance) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </details>
  </div>
</template>
