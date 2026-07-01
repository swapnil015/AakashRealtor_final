<script setup lang="ts">
import type { Category, City, Requirement } from '~/types'
import { ApiError } from '~/composables/useApi'

const api = useApi()

const { data: refs } = await useAsyncData('req-refs', async () => {
  const [cats, cities] = await Promise.all([
    api.get<Category[]>('/categories'),
    api.get<City[]>('/cities'),
  ])
  return { categories: cats.data, cities: cities.data }
})

const { data: list, refresh } = await useAsyncData('requirements-list', () =>
  api.get<Requirement[]>('/requirements', { per_page: 9 }),
)

const form = reactive<Record<string, any>>({
  name: '', phone: '', email: '',
  transaction_type: 'buy', category_id: null, city_id: null,
  min_budget: null, max_budget: null, message: '', website: '', _ts: 0,
})
const sending = ref(false)
const sent = ref(false)
const error = ref('')
onMounted(() => { form._ts = Date.now() })

async function submit() {
  sending.value = true
  error.value = ''
  try {
    await api.post('/requirements', form)
    sent.value = true
    refresh()
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Could not submit.'
  } finally {
    sending.value = false
  }
}

useSeoMeta({
  title: 'Post a Requirement',
  description: "Didn't find your property? Tell Aakash Realtor what you need and we'll alert you on matches.",
})
</script>

<template>
  <div class="container-px py-28">
    <div class="grid gap-12 lg:grid-cols-[1fr_1fr]">
      <div>
        <p class="eyebrow">Didn't find it?</p>
        <h1 class="mt-3 font-display text-5xl font-semibold">Tell us what you're looking for.</h1>
        <p class="mt-4 max-w-md text-muted">
          Post your requirement and our team will alert you the moment a matching, lalpurja-verified
          property comes to market.
        </p>

        <div v-if="sent" class="mt-8 rounded-2xl bg-green-50 p-8 text-center">
          <div class="text-4xl">✓</div>
          <p class="mt-3 font-display text-2xl">Requirement posted!</p>
          <p class="mt-1 text-muted">We'll be in touch as soon as we find a match.</p>
        </div>

        <form v-else class="mt-8 space-y-4" @submit.prevent="submit">
          <div class="grid grid-cols-2 gap-3">
            <input v-model="form.name" placeholder="Your name" required class="field" />
            <input v-model="form.phone" placeholder="Phone" required class="field" />
          </div>
          <input v-model="form.email" type="email" placeholder="Email (optional)" class="field" />
          <div class="inline-flex rounded-lg bg-sand p-1">
            <button v-for="t in ['buy','rent']" :key="t" type="button"
              class="rounded-md px-5 py-2 text-sm font-bold capitalize transition"
              :class="form.transaction_type === t ? 'bg-gold text-ink' : 'text-muted'"
              @click="form.transaction_type = t">{{ t }}</button>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <select v-model.number="form.category_id" required class="field">
              <option :value="null">Category</option>
              <option v-for="c in refs?.categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <select v-model.number="form.city_id" required class="field">
              <option :value="null">City</option>
              <option v-for="c in refs?.cities" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <input v-model.number="form.min_budget" type="number" placeholder="Min budget (Rs.)" class="field" />
            <input v-model.number="form.max_budget" type="number" placeholder="Max budget (Rs.)" class="field" />
          </div>
          <textarea v-model="form.message" rows="3" placeholder="Anything specific?" class="field resize-none" />
          <input v-model="form.website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />
          <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
          <button :disabled="sending" class="btn-gold w-full">{{ sending ? 'Posting…' : 'Post Requirement' }}</button>
        </form>
      </div>

      <!-- Demand wall -->
      <div>
        <h2 class="font-display text-3xl font-semibold">Buyers currently looking</h2>
        <div class="mt-6 space-y-3">
          <div v-for="r in list?.data" :key="r.id" class="rounded-xl border border-slate-200 bg-white p-5">
            <div class="flex items-center justify-between">
              <span class="rounded-full bg-gold/10 px-3 py-1 text-xs font-bold uppercase tracking-wide text-gold-hover">
                {{ r.transaction_type }} · {{ r.category?.name }}
              </span>
              <span class="text-sm font-semibold text-muted">{{ r.city?.name }}</span>
            </div>
            <p v-if="r.budget?.max" class="mt-3 font-display text-xl font-semibold">
              Up to Rs. {{ Number(r.budget.max).toLocaleString() }}
            </p>
            <p v-if="r.message" class="mt-1 text-sm text-muted">{{ r.message }}</p>
          </div>
          <p v-if="!list?.data?.length" class="text-muted">No open requirements yet — be the first.</p>
        </div>
      </div>
    </div>
  </div>
</template>
