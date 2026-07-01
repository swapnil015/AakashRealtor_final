<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { ApiError } from '~/composables/useApi'

const auth = useAuthStore()
const route = useRoute()

const form = reactive({ login: '', password: '' })
const loading = ref(false)
const error = ref('')

// Where to go after login, and a contextual notice when the user was bounced
// here from a protected page (e.g. clicking "Post Property" while logged out).
const redirect = computed(() => (route.query.redirect as string) || '/dashboard')
const gateMessage = computed(() => {
  const r = route.query.redirect as string | undefined
  if (!r) return ''
  if (r.startsWith('/post')) return 'Please log in to post a property.'
  if (r.startsWith('/dashboard')) return 'Please log in to view your dashboard.'
  return 'Please log in to continue.'
})

async function submit() {
  loading.value = true
  error.value = ''
  try {
    await auth.login(form.login, form.password)
    navigateTo(redirect.value)
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Login failed.'
  } finally {
    loading.value = false
  }
}

useSeoMeta({ title: 'Log in', robots: 'noindex' })
</script>

<template>
  <div class="container-px grid min-h-screen place-items-center py-28">
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-card">
      <h1 class="font-display text-4xl font-semibold">Welcome back</h1>
      <p class="mt-2 text-muted">Log in to manage your listings and saved homes.</p>

      <!-- Shown when redirected here from a protected page. -->
      <p v-if="gateMessage" class="mt-5 rounded-xl bg-gold/10 px-4 py-3 text-sm font-semibold text-gold-hover">
        🔒 {{ gateMessage }}
      </p>

      <form class="mt-7 space-y-4" @submit.prevent="submit">
        <input v-model="form.login" placeholder="Email or phone" required class="field" />
        <input v-model="form.password" type="password" placeholder="Password" required class="field" />
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
        <button :disabled="loading" class="btn-gold w-full">{{ loading ? 'Logging in…' : 'Log in' }}</button>
      </form>

      <div class="mt-5 flex items-center justify-between text-sm">
        <NuxtLink to="/forgot-password" class="text-muted hover:text-gold">Forgot password?</NuxtLink>
        <NuxtLink :to="{ path: '/register', query: route.query }" class="font-semibold text-gold-hover">Create account →</NuxtLink>
      </div>
    </div>
  </div>
</template>
