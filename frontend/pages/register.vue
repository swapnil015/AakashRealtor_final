<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { ApiError } from '~/composables/useApi'

const auth = useAuthStore()
const route = useRoute()
const redirect = computed(() => (route.query.redirect as string) || '/dashboard')
const form = reactive({ name: '', email: '', phone: '', password: '', password_confirmation: '' })
const loading = ref(false)
const error = ref('')
const fieldErrors = ref<Record<string, string[]>>({})

async function submit() {
  loading.value = true
  error.value = ''
  fieldErrors.value = {}
  try {
    await auth.register(form)
    navigateTo(redirect.value)
  } catch (e) {
    if (e instanceof ApiError) {
      error.value = e.message
      fieldErrors.value = e.errors
    } else {
      error.value = 'Registration failed.'
    }
  } finally {
    loading.value = false
  }
}

useSeoMeta({ title: 'Create account', robots: 'noindex' })
</script>

<template>
  <div class="container-px grid min-h-screen place-items-center py-28">
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-card">
      <h1 class="font-display text-4xl font-semibold">Create your account</h1>
      <p class="mt-2 text-muted">List properties, save favorites and post requirements.</p>

      <form class="mt-7 space-y-4" @submit.prevent="submit">
        <div>
          <input v-model="form.name" placeholder="Full name" required class="field" />
          <p v-if="fieldErrors.name" class="mt-1 text-xs text-red-600">{{ fieldErrors.name[0] }}</p>
        </div>
        <div>
          <input v-model="form.email" type="email" placeholder="Email" required class="field" />
          <p v-if="fieldErrors.email" class="mt-1 text-xs text-red-600">{{ fieldErrors.email[0] }}</p>
        </div>
        <div>
          <input v-model="form.phone" placeholder="Phone (optional)" class="field" />
          <p v-if="fieldErrors.phone" class="mt-1 text-xs text-red-600">{{ fieldErrors.phone[0] }}</p>
        </div>
        <input v-model="form.password" type="password" placeholder="Password (min 8, letters + numbers)" required class="field" />
        <input v-model="form.password_confirmation" type="password" placeholder="Confirm password" required class="field" />
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
        <button :disabled="loading" class="btn-gold w-full">{{ loading ? 'Creating…' : 'Create account' }}</button>
      </form>

      <p class="mt-5 text-center text-sm text-muted">
        Already have an account? <NuxtLink to="/login" class="font-semibold text-gold-hover">Log in</NuxtLink>
      </p>
    </div>
  </div>
</template>
