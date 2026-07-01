<script setup lang="ts">
import type { NuxtError } from '#app'

const props = defineProps<{ error: NuxtError }>()
const is404 = computed(() => props.error?.statusCode === 404)
</script>

<template>
  <div class="grid min-h-screen place-items-center bg-canvas px-6 text-center">
    <div>
      <div class="font-display text-8xl font-bold text-gold">{{ error.statusCode || 500 }}</div>
      <h1 class="mt-4 font-display text-4xl font-semibold">
        {{ is404 ? 'Page not found' : 'Something went wrong' }}
      </h1>
      <p class="mt-3 text-muted">
        {{ is404 ? "The page you're looking for doesn't exist or has moved." : error.message }}
      </p>
      <button class="btn-gold mt-8" @click="clearError({ redirect: '/' })">Back to home</button>
    </div>
  </div>
</template>
