<script setup lang="ts">
import { ApiError } from '~/composables/useApi'

const api = useApi()
const route = useRoute()

const form = reactive({
  token: (route.query.token as string) || '',
  email: (route.query.email as string) || '',
  password: '',
  password_confirmation: '',
})
const loading = ref(false)
const done = ref(false)
const error = ref('')

async function submit() {
  loading.value = true
  error.value = ''
  try {
    await api.post('/auth/reset-password', form)
    done.value = true
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Reset failed.'
  } finally {
    loading.value = false
  }
}
useSeoMeta({ title: 'Set New Password', robots: 'noindex' })
</script>

<template>
  <div class="container-px grid min-h-screen place-items-center py-28">
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-card">
      <h1 class="font-display text-4xl font-semibold">New password</h1>
      <div v-if="done" class="mt-6 rounded-xl bg-green-50 p-6 text-center">
        <p class="font-semibold">Password updated</p>
        <NuxtLink to="/login" class="btn-gold mt-4">Log in</NuxtLink>
      </div>
      <form v-else class="mt-6 space-y-4" @submit.prevent="submit">
        <input v-model="form.email" type="email" placeholder="Email" required class="field" />
        <input v-model="form.password" type="password" placeholder="New password" required class="field" />
        <input v-model="form.password_confirmation" type="password" placeholder="Confirm password" required class="field" />
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
        <button :disabled="loading" class="btn-gold w-full">{{ loading ? 'Saving…' : 'Reset password' }}</button>
      </form>
    </div>
  </div>
</template>
