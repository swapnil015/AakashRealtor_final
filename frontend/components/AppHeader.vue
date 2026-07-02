<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import type { Category, City } from '~/types'

const auth = useAuthStore()
const route = useRoute()
const api = useApi()

const mobileOpen = ref(false)
const megaOpen = ref<null | 'buy' | 'rent'>(null)

// Reference data for the Buy/Rent mega-menu (category × city).
const { data: nav } = await useAsyncData('header-nav', async () => {
  const [cats, cities] = await Promise.all([
    api.get<Category[]>('/categories'),
    api.get<City[]>('/cities', { popular: 1 }),
  ])
  return { categories: cats.data, cities: cities.data }
})

const categories = computed(() => nav.value?.categories ?? [])
const cities = computed(() => nav.value?.cities ?? [])

watch(() => route.fullPath, () => { mobileOpen.value = false; megaOpen.value = null })

function listingUrl(txn: 'buy' | 'rent', cat: Category, city?: City) {
  const seg = `${txn}${cat.name.replace(/\s+/g, '')}`
  return city ? `/${seg}/${city.url_token}` : `/${seg}`
}

async function doLogout() {
  await auth.logout()
  navigateTo('/')
}
</script>

<template>
  <header class="sticky top-0 z-50 w-full border-b border-outline-variant bg-surface" @mouseleave="megaOpen = null">
    <div class="mx-auto flex max-w-shell items-center justify-between px-margin-mobile py-4 md:px-margin-desktop">
      <!-- Wordmark -->
      <NuxtLink to="/" class="flex items-center gap-3">
        <span class="material-symbols-outlined text-primary md:hidden">menu</span>
        <span class="font-display text-headline-md uppercase tracking-tight text-primary">Aakash Realtor</span>
      </NuxtLink>

      <!-- Desktop nav -->
      <nav class="hidden items-center gap-8 md:flex">
        <NuxtLink to="/" class="font-sans text-label-caps uppercase tracking-[0.1em] text-on-surface-variant transition-colors hover:text-secondary"
          :class="route.path === '/' && 'border-b-2 border-primary text-primary'">Home</NuxtLink>
        <button class="font-sans text-label-caps uppercase tracking-[0.1em] text-on-surface-variant transition-colors hover:text-secondary" @mouseenter="megaOpen = 'buy'">Buy</button>
        <button class="font-sans text-label-caps uppercase tracking-[0.1em] text-on-surface-variant transition-colors hover:text-secondary" @mouseenter="megaOpen = 'rent'">Rent</button>
        <NuxtLink to="/exclusive" class="font-sans text-label-caps uppercase tracking-[0.1em] text-on-surface-variant transition-colors hover:text-secondary">Invest</NuxtLink>
        <NuxtLink to="/about" class="font-sans text-label-caps uppercase tracking-[0.1em] text-on-surface-variant transition-colors hover:text-secondary">About</NuxtLink>
        <NuxtLink to="/tools/emi" class="font-sans text-label-caps uppercase tracking-[0.1em] text-on-surface-variant transition-colors hover:text-secondary">Tools</NuxtLink>
        <NuxtLink to="/contact" class="font-sans text-label-caps uppercase tracking-[0.1em] text-on-surface-variant transition-colors hover:text-secondary">Contact</NuxtLink>
      </nav>

      <!-- Right side -->
      <div class="flex items-center gap-3">
        <template v-if="auth.isLoggedIn">
          <NuxtLink to="/dashboard" class="hidden font-sans text-label-caps uppercase tracking-[0.1em] text-primary hover:text-secondary sm:block">
            {{ auth.user?.name?.split(' ')[0] || 'Account' }}
          </NuxtLink>
          <button class="hidden font-sans text-label-caps uppercase tracking-[0.1em] text-on-surface-variant hover:text-secondary sm:block" @click="doLogout">Log out</button>
          <NuxtLink to="/post" class="border border-primary px-4 py-2 font-sans text-label-caps uppercase tracking-[0.1em] text-primary transition-all hover:bg-primary hover:text-surface">Post</NuxtLink>
        </template>
        <template v-else>
          <NuxtLink to="/login" class="border border-primary px-4 py-2 font-sans text-label-caps uppercase tracking-[0.1em] text-primary transition-all hover:bg-primary hover:text-surface">
            Login / Register
          </NuxtLink>
        </template>
        <button class="md:hidden" aria-label="Menu" @click="mobileOpen = !mobileOpen">
          <span class="material-symbols-outlined text-primary">{{ mobileOpen ? 'close' : 'menu' }}</span>
        </button>
      </div>
    </div>

    <!-- Mega-menu (category × city) -->
    <Transition name="page">
      <div v-if="megaOpen" class="absolute inset-x-0 top-full hidden border-t border-outline-variant bg-surface md:block" @mouseenter="() => {}">
        <div class="mx-auto grid max-w-shell grid-cols-12 gap-content-gap px-margin-desktop py-8">
          <div class="col-span-5">
            <p class="eyebrow mb-4">{{ megaOpen === 'buy' ? 'Buy by category' : 'Rent by category' }}</p>
            <div class="grid grid-cols-2 gap-1">
              <NuxtLink v-for="c in categories" :key="c.id" :to="listingUrl(megaOpen, c)"
                class="flex items-center justify-between px-2 py-2 font-sans text-body-md text-primary transition-colors hover:text-secondary">
                <span>{{ c.name }}</span>
                <span v-if="c.properties_count" class="font-sans text-technical-data text-on-surface-variant">{{ c.properties_count }}</span>
              </NuxtLink>
            </div>
          </div>
          <div class="col-span-7 border-l border-outline-variant pl-8">
            <p class="eyebrow mb-4">Popular cities</p>
            <div class="grid grid-cols-2 gap-1">
              <NuxtLink v-for="city in cities" :key="city.id" :to="`/${megaOpen}House/${city.url_token}`"
                class="flex items-center justify-between px-2 py-2 font-sans text-body-md text-primary transition-colors hover:text-secondary">
                <span>{{ city.name }}</span>
                <span class="font-sans text-technical-data text-on-surface-variant">{{ city.properties_count ?? '' }}</span>
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Mobile drawer -->
    <Transition name="page">
      <div v-if="mobileOpen" class="border-t border-outline-variant bg-surface md:hidden">
        <div class="px-margin-mobile py-4">
          <NuxtLink v-for="c in categories" :key="c.id" :to="listingUrl('buy', c)"
            class="block py-2.5 font-sans text-body-md text-primary">Buy {{ c.name }}</NuxtLink>
          <hr class="my-2 border-outline-variant" />
          <NuxtLink to="/exclusive" class="block py-2.5 font-sans text-body-md text-primary">Invest</NuxtLink>
          <NuxtLink to="/about" class="block py-2.5 font-sans text-body-md text-primary">About</NuxtLink>
          <NuxtLink to="/tools/emi" class="block py-2.5 font-sans text-body-md text-primary">Tools</NuxtLink>
          <NuxtLink to="/contact" class="block py-2.5 font-sans text-body-md text-primary">Contact</NuxtLink>
          <NuxtLink to="/post" class="mt-3 block bg-primary py-3 text-center font-sans text-label-caps uppercase tracking-[0.1em] text-surface">Post Property</NuxtLink>
          <NuxtLink v-if="!auth.isLoggedIn" to="/login" class="mt-2 block border border-primary py-3 text-center font-sans text-label-caps uppercase tracking-[0.1em] text-primary">Login / Register</NuxtLink>
        </div>
      </div>
    </Transition>
  </header>
</template>
