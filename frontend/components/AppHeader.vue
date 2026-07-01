<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import type { Category, City } from '~/types'

const auth = useAuthStore()
const route = useRoute()
const api = useApi()

const scrolled = ref(false)
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

// transparent over hero on home, solid elsewhere / once scrolled.
const transparent = computed(() => route.path === '/' && !scrolled.value)

function onScroll() {
  scrolled.value = window.scrollY > 36
}
onMounted(() => {
  onScroll()
  window.addEventListener('scroll', onScroll, { passive: true })
})
onUnmounted(() => window.removeEventListener('scroll', onScroll))

watch(() => route.fullPath, () => {
  mobileOpen.value = false
  megaOpen.value = null
})

// Build a listing URL: /buyHouse/Kathmandu-53
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
  <header
    class="fixed inset-x-0 top-0 z-50 transition-all duration-300 ease-smooth"
    :class="transparent
      ? 'py-5 bg-transparent'
      : 'py-3 bg-canvas/85 backdrop-blur-xl shadow-[0_6px_26px_-14px_rgba(15,23,42,.3)]'"
    @mouseleave="megaOpen = null"
  >
    <div class="container-px flex items-center gap-6">
      <!-- Logo -->
      <NuxtLink to="/" class="flex items-center gap-3 shrink-0">
        <span class="grid h-10 w-10 place-items-center rounded-[10px] bg-gold font-display text-xl font-bold text-ink">A</span>
        <span class="font-display text-xl font-bold tracking-wide"
              :class="transparent ? 'text-white' : 'text-ink'">AAKASH REALTOR</span>
      </NuxtLink>

      <!-- Desktop nav -->
      <nav class="ml-2 hidden items-center gap-1 lg:flex"
           :class="transparent ? 'text-white' : 'text-ink'">
        <button
          v-for="t in (['buy','rent'] as const)" :key="t"
          class="nav-underline px-3 py-2 text-sm font-semibold capitalize transition hover:text-gold"
          @mouseenter="megaOpen = t"
        >{{ t }}</button>
        <NuxtLink to="/exclusive" class="nav-underline px-3 py-2 text-sm font-semibold transition hover:text-gold">Exclusive</NuxtLink>
        <NuxtLink to="/about" class="nav-underline px-3 py-2 text-sm font-semibold transition hover:text-gold">About</NuxtLink>
        <NuxtLink to="/tools/emi" class="nav-underline px-3 py-2 text-sm font-semibold transition hover:text-gold">Tools</NuxtLink>
        <NuxtLink to="/contact" class="nav-underline px-3 py-2 text-sm font-semibold transition hover:text-gold">Contact</NuxtLink>
      </nav>

      <!-- Right -->
      <div class="ml-auto flex items-center gap-3"
           :class="transparent ? 'text-white' : 'text-ink'">
        <template v-if="auth.isLoggedIn">
          <NuxtLink to="/dashboard" class="hidden text-sm font-semibold transition hover:text-gold sm:block">
            {{ auth.user?.name?.split(' ')[0] || 'Account' }}
          </NuxtLink>
          <button class="hidden text-sm font-semibold transition hover:text-gold sm:block" @click="doLogout">Log out</button>
        </template>
        <template v-else>
          <NuxtLink to="/login" class="hidden text-sm font-semibold transition hover:text-gold sm:block">Log in</NuxtLink>
        </template>
        <NuxtLink v-magnetic="0.35" to="/post" class="hidden rounded-xl bg-gold px-5 py-3 text-sm font-bold text-ink shadow-sm transition-shadow hover:shadow-gold sm:inline-flex">
          + Post Property
        </NuxtLink>
        <!-- Mobile toggle -->
        <button class="lg:hidden" aria-label="Menu" @click="mobileOpen = !mobileOpen">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 6h18M3 12h18M3 18h18" stroke-linecap="round" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Mega-menu (category × city) -->
    <Transition name="page">
      <div v-if="megaOpen" class="absolute inset-x-0 top-full hidden lg:block"
           @mouseenter="() => {}">
        <div class="container-px pt-2">
          <div class="grid grid-cols-12 gap-8 rounded-2xl border border-slate-200 bg-white p-8 shadow-lift">
            <div class="col-span-5">
              <div class="eyebrow mb-4">{{ megaOpen === 'buy' ? 'Buy by category' : 'Rent by category' }}</div>
              <div class="grid grid-cols-2 gap-2">
                <NuxtLink v-for="c in categories" :key="c.id" :to="listingUrl(megaOpen, c)"
                  class="rounded-lg px-3 py-2.5 text-sm font-semibold text-ink transition hover:bg-sand hover:text-gold-hover">
                  {{ c.name }}
                  <span v-if="c.properties_count" class="ml-1 text-xs font-medium text-muted">{{ c.properties_count }}</span>
                </NuxtLink>
              </div>
            </div>
            <div class="col-span-7 border-l border-slate-100 pl-8">
              <div class="eyebrow mb-4">Popular cities</div>
              <div class="grid grid-cols-2 gap-2">
                <NuxtLink v-for="city in cities" :key="city.id"
                  :to="`/${megaOpen}House/${city.url_token}`"
                  class="flex items-center justify-between rounded-lg px-3 py-2.5 text-sm font-semibold text-ink transition hover:bg-sand hover:text-gold-hover">
                  <span>{{ city.name }}</span>
                  <span class="text-xs font-medium text-muted">{{ city.properties_count ?? '' }}</span>
                </NuxtLink>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Mobile drawer -->
    <Transition name="page">
      <div v-if="mobileOpen" class="container-px mt-3 lg:hidden">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-lift">
          <NuxtLink v-for="c in categories" :key="c.id" :to="listingUrl('buy', c)"
            class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-ink hover:bg-sand">Buy {{ c.name }}</NuxtLink>
          <hr class="my-2 border-slate-100" />
          <NuxtLink to="/exclusive" class="block rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-sand">Exclusive</NuxtLink>
          <NuxtLink to="/about" class="block rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-sand">About</NuxtLink>
          <NuxtLink to="/tools/emi" class="block rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-sand">Tools</NuxtLink>
          <NuxtLink to="/contact" class="block rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-sand">Contact</NuxtLink>
          <NuxtLink to="/post" class="mt-2 block rounded-xl bg-gold px-3 py-3 text-center text-sm font-bold text-ink">+ Post Property</NuxtLink>
          <NuxtLink v-if="!auth.isLoggedIn" to="/login" class="mt-1 block rounded-lg px-3 py-2.5 text-center text-sm font-semibold">Log in</NuxtLink>
        </div>
      </div>
    </Transition>
  </header>
</template>
