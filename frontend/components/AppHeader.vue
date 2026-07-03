<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
const route = useRoute()

const mobileOpen = ref(false)
const scrolled = ref(false)

function onScroll() { scrolled.value = window.scrollY > 100 }
onMounted(() => {
  onScroll()
  window.addEventListener('scroll', onScroll, { passive: true })
})
onUnmounted(() => window.removeEventListener('scroll', onScroll))

watch(() => route.fullPath, () => { mobileOpen.value = false })

const links = [
  { label: 'Listings', to: '/buyHouse' },
  { label: 'Invest', to: '/exclusive' },
  { label: 'Insights', to: '/blogs' },
  { label: 'About', to: '/about' },
  { label: 'Contact', to: '/contact' },
]

function isActive(to: string) {
  return to === '/' ? route.path === '/' : route.path.startsWith(to)
}

async function doLogout() {
  await auth.logout()
  navigateTo('/')
}
</script>

<template>
  <header
    class="fixed top-0 z-50 flex h-20 w-full items-center justify-between border-b border-outline-variant bg-surface/95 px-margin-mobile backdrop-blur-md transition-shadow md:px-margin-desktop"
    :class="scrolled && 'shadow-sm'"
  >
    <div class="flex items-center gap-12">
      <NuxtLink to="/" class="font-display text-headline-sm font-bold text-primary">Aakash Realtor</NuxtLink>
      <nav class="hidden items-center gap-8 md:flex">
        <NuxtLink v-for="l in links" :key="l.to" :to="l.to"
          class="pb-1 font-sans text-label-caps uppercase tracking-[0.1em] transition-colors"
          :class="isActive(l.to)
            ? 'border-b border-primary text-primary'
            : 'text-on-surface-variant hover:text-secondary'">
          {{ l.label }}
        </NuxtLink>
      </nav>
    </div>

    <div class="flex items-center gap-6">
      <span class="hidden cursor-pointer font-sans text-label-caps uppercase tracking-[0.1em] text-on-surface-variant transition-colors hover:text-primary sm:block">EN/नेपाली</span>

      <template v-if="auth.isLoggedIn">
        <NuxtLink to="/dashboard" class="hidden font-sans text-label-caps uppercase tracking-[0.1em] text-primary hover:text-secondary sm:block">
          {{ auth.user?.name?.split(' ')[0] || 'Account' }}
        </NuxtLink>
        <button class="hidden font-sans text-label-caps uppercase tracking-[0.1em] text-on-surface-variant hover:text-secondary sm:block" @click="doLogout">Logout</button>
        <NuxtLink to="/post" class="hidden border border-primary px-4 py-2 font-sans text-label-caps uppercase tracking-[0.1em] text-primary transition-all hover:bg-primary hover:text-surface sm:block">Post</NuxtLink>
      </template>
      <template v-else>
        <NuxtLink to="/login" class="hidden border border-primary px-4 py-2 font-sans text-label-caps uppercase tracking-[0.1em] text-primary transition-all hover:bg-primary hover:text-surface sm:block">
          Login / Register
        </NuxtLink>
      </template>

      <NuxtLink to="/buyHouse" aria-label="Search" class="hidden sm:block">
        <span class="material-symbols-outlined cursor-pointer text-primary">search</span>
      </NuxtLink>

      <button class="md:hidden" aria-label="Menu" @click="mobileOpen = !mobileOpen">
        <span class="material-symbols-outlined text-primary">{{ mobileOpen ? 'close' : 'menu' }}</span>
      </button>
    </div>

    <!-- Mobile drawer -->
    <Transition name="page">
      <div v-if="mobileOpen" class="absolute inset-x-0 top-full border-b border-outline-variant bg-surface md:hidden">
        <div class="px-margin-mobile py-4">
          <NuxtLink v-for="l in links" :key="l.to" :to="l.to"
            class="block py-3 font-sans text-label-caps uppercase tracking-[0.1em] text-primary">{{ l.label }}</NuxtLink>
          <hr class="my-2 border-outline-variant" />
          <NuxtLink to="/post" class="mt-2 block bg-primary py-3.5 text-center font-sans text-label-caps uppercase tracking-[0.15em] text-white">Post Property</NuxtLink>
          <NuxtLink v-if="!auth.isLoggedIn" to="/login" class="mt-2 block border border-primary py-3.5 text-center font-sans text-label-caps uppercase tracking-[0.15em] text-primary">Login / Register</NuxtLink>
          <template v-else>
            <NuxtLink to="/dashboard" class="mt-2 block border border-primary py-3.5 text-center font-sans text-label-caps uppercase tracking-[0.15em] text-primary">My Account</NuxtLink>
          </template>
        </div>
      </div>
    </Transition>
  </header>
</template>
