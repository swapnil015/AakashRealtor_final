<script setup lang="ts">
const api = useApi()
const email = ref('')
const sent = ref(false)
const loading = ref(false)

async function submit() {
  loading.value = true
  try {
    await api.post('/auth/forgot-password', { email: email.value })
    sent.value = true
  } finally {
    loading.value = false
  }
}
useSeoMeta({ title: 'Forgot Password', robots: 'noindex' })
</script>

<template>
  <div class="container-px grid min-h-screen place-items-center py-28">
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-card">
      <h1 class="font-display text-4xl font-semibold">Reset password</h1>
      <div v-if="sent" class="mt-6 rounded-xl bg-green-50 p-6 text-center">
        <p class="font-semibold">Check your inbox</p>
        <p class="mt-1 text-sm text-muted">If that email is registered, we've sent a reset link.</p>
      </div>
      <form v-else class="mt-6 space-y-4" @submit.prevent="submit">
        <input v-model="email" type="email" placeholder="Your email" required class="field" />
        <button :disabled="loading" class="btn-gold w-full">{{ loading ? 'Sending…' : 'Send reset link' }}</button>
      </form>
      <NuxtLink to="/login" class="mt-5 block text-center text-sm font-semibold text-gold-hover">← Back to login</NuxtLink>
    </div>
  </div>
</template>
